angular.module('MtClab',['ngRoute','Directives', 'TokenModule'/*,'SecurityModule'*/,'ResourceModule','LanguageModule','NavigationModule','IndexModule','AppsModule','OptionsModule','UserModule','LoginModule']).
	
	config(function($routeProvider, $locationProvider, HeadersProvider, CredentialsProvider) {
        $routeProviderReference = $routeProvider;
        $locationProvider.html5Mode(false).hashPrefix('!');
		HeadersProvider.setCredentials(CredentialsProvider.getCredentials());
    }).
	
	filter('searchFor', function() {
		return function (arr, searchString){
			if (!searchString){
				return arr;
			}
			var result = [];
			searchString = searchString.toLowerCase();
			//console.log(searchString);
			angular.forEach(arr, function(item){
				if(item.name.toLowerCase().indexOf(searchString) !== -1){
					result.push(item);
				}
			});
			return result;
		};
	}).
	
	filter('filterField', function() {
		console.log('check');
		return function (arr, field) {
			if (!item) {
				return arr;
			}
			var result = [];
			angular.forEach(arr, function (item) {
				if (item[field] === 0) {
					result.push(item);
				}
			});
			return result;
		}
	}).
	
	factory('LanguageFactory', ['Resource', function (Resource) {
        return Resource('lang');
    }]).
	
	factory('Logout', ['Resource', function (Resource) {
        return Resource('logout');
    }]).
	
	factory('Menu', ['Resource', function (Resource) {
        return Resource('menu');
    }]).

	run(['$rootScope', '$location', '$route', '$translate', 'Menu', 'Language', 'LanguageFactory', 'Credentials', 'Navigation', 'Logout', function($rootScope, $location, $route, $translate, Menu, Language, LanguageFactory, Credentials, Navigation, Logout) {
		var tempCtrl
		$rootScope.afterLanguage = function () {
			$translate.uses(Language.getLanguage());
			$rootScope.$on( "$routeChangeStart", function(event, current, previous) {
				console.log(current.params);
				if (current.params.lang !== Language.getLanguage()) {
					LanguageFactory.setId(current.params.lang);
					LanguageFactory.get().then(function (response) {
						var langs = response.data;
						Language.setLanguage(langs.lang);
						$translateProviderReference.translations(langs.lang, langs);
						if (Language.isSessionStorage()) sessionStorage.setItem('langs', JSON.stringify(langs));
						$rootScope.rootScopeOn(event, current, previous);
					});	
				} else {
					$rootScope.rootScopeOn(event, current, previous);						
				}
			});				
		}
		
		$rootScope.rootScopeOn = function (event, current, previous) {
			console.log('$route: ', current);
			console.warn('$route: ', previous);
			console.info('$route: ', event);
			$translate.uses(Language.getLanguage());
			if (typeof(current.params.option) !== 'undefined') {
				current.params.option = Navigation.getItem(current.params.option);//translations[current.params.option];
				if (typeof(current.params.action) !== 'undefined') {
					current.params.action = Navigation.getItem(current.params.action);//translations[current.params.action];
				}
				console.log(current.params);
			}
		}

		$routeProviderReference.
			when('/:lang', { templateUrl: 'common/index/index.tpl.html', controller: 'IndexCtrl' }).
			when('/:lang/:option', {
				templateUrl: function(url) {
					if (!Language.getLanguage() || Language.getLanguage() !== url.lang) {
						$location.path( url.lang );
					} else {
						url.url     = 'common/' + url.option + '/' + url.option + '.tpl.html';
						tempCtrl 	= php.ucfirst(url.option) + 'Ctrl';
					}
					return url.url;
				},
				controller: tempCtrl
			}).
			
			when('/:lang/:option/:action', {
				templateUrl: function(url) {
					console.log(url);
					if (!Language.getLanguage() || Language.getLanguage() !== url.lang) {
						$location.path( url.lang );
					} else {               
						url.url     = 'common/' + url.option + '/' + url.option + '.' + url.action + '.tpl.html';
						tempCtrl 	= php.ucfirst(url.action) + 'Ctrl';
					}
					return url.url;
				},
				controller: tempCtrl
			}).
			
			when('/:lang/:option/:action/:id', {
				templateUrl: function(url) {
					console.log(url);
					if (!Language.getLanguage() || Language.getLanguage() !== url.lang) {
						$location.path( url.lang );
					} else {               
						url.url     = 'common/' + url.option + '/' + url.option + '.' + url.action + '.tpl.html';
						tempCtrl 	= php.ucfirst(url.action) + 'Ctrl';
					}
					return url.url;
				},
				controller: tempCtrl
			}).
	
			otherwise({ redirectTo: '/nl/'});

		$rootScope.Navigation = function () {
			Navigation.setLanguage(Language.getLanguage());
			if (Navigation.isMenuLoaded()) {
				console.log('menu loaded');
				$rootScope.afterLanguage();
			} else {
				Menu.query().then(function (response) {
					Navigation.setMenu(response.data);
					$rootScope.afterLanguage();
				});
			}
		}
		
		if (Language.isSessionStorage()) {
			if (Language.isLanguageInSession()) {
				var langs = JSON.parse(sessionStorage.getItem('langs'));
				$translateProviderReference.translations(langs.lang, langs);
				Language.setLanguage(langs.lang);
				console.log('language loaded from session');
				$rootScope.Navigation();
			} else {
				LanguageFactory.setId(Language.getLanguage());
				LanguageFactory.get().then(function (response) {
					var langs = response.data;
					sessionStorage.setItem('langs', JSON.stringify(langs));
					$translateProviderReference.translations(langs.lang, langs);
					console.log('language loaded from rest (to session)');
					$rootScope.Navigation();
				});
			}
		} else {
			LanguageFactory.setId(Language.getLanguage());
			LanguageFactory.get().then(function (response) {
				var langs = response.data;
				$translateProviderReference.translations(langs.lang, langs);
				console.log('language loaded from rest (no session)');
				NavigationProvider();
			});
		}
		
		$rootScope.isAuthenticated = function () {
            return Credentials.isAuthenticated();
        }

		$rootScope.logout = function () {
			console.log(Credentials.getHeaders());
			Logout.post().then(function (response) {
				console.log(sessionStorage);
				Credentials.setUser(false, false);
				$location.path('');
			});
		}
		
	}]);
	
// moet nog eigen file krijgen
angular.module('Directives',[]).

	directive('required', function() {
        return {
			restrict: 'A',
			link: function(scope, element) {
				var html = '<div class="required">*</div>';
				element.parent().append(html);
			}
        }
    });

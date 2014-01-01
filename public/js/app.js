angular.module('MtClab',['ngRoute','Directives', 'TokenModule'/*,'SecurityModule'*/,'ResourceModule','LanguageModule','NavigationModule','IndexModule','AppsModule','OptionsModule','UserModule','LoginModule']).
	
	config(function($routeProvider, $locationProvider, HeadersProvider, CredentialsProvider) {
        $routeProviderReference = $routeProvider;
        $locationProvider.html5Mode(false);
        $locationProvider.hashPrefix('!');
		HeadersProvider.setCredentials(CredentialsProvider.getCredentials());
    }).
	
	filter('searchFor', function(){
		return function(arr, searchString){
			if(!searchString){
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

	controller('LanguageCtrl',['$scope', '$location', 'Language', function($scope, $location, Language){
		$scope.go = function ( lang ) {
			if (Language.isSessionStorage()) sessionStorage.setItem('lang', lang);
			$location.path( lang );
		};
	}]).

	run(['$rootScope', '$location', '$route', 'Token', 'XSRF', 'Menu', 'Language', 'Credentials', function($rootScope, $location, $route, Token, XSRF, Menu, Language, Credentials) {
		var tempCtrl;
		
		//console.log(Resource('trials');
		//console.log(Resource.getKieke());

		console.log(Language.getLanguage());
		
		
		
		if (window.sessionStorage) {
			Language.setSessionStorage(true);
			if (sessionStorage.getItem('lang')) {
				Language.setLanguage(sessionStorage.getItem('lang'));
				//$route.reload();//(sessionStorage.getItem('lang'));
				console.log('reload language');
				Language.setReload(true);
			}
			if (!Credentials.isAuthenticated() && sessionStorage.getItem('user')) {
				Credentials.setUser(sessionStorage.getItem('user'), sessionStorage.getItem('role'));
				Token.set(sessionStorage.getItem('token'));
				XSRF.set(sessionStorage.getItem('xsrf'));
			}
			console.log(sessionStorage);
		}
		
		
		$rootScope.$on( "$routeChangeStart", function(event, next, current) {
			var translations = Menu.get();
			if (typeof(translations.init) !== 'undefined') {
				if (typeof(next.params.option) !== 'undefined') {
					next.params.option = translations[next.params.option];
				}
				if (typeof(next.params.action) !== 'undefined') {
					next.params.action = translations[next.params.action];
				}
			} else {
				var path = Language.isSessionStorage() && sessionStorage.getItem('lang') ? sessionStorage.getItem('lang') : 'nl';
				$location.path( path );
			}
		});
			
		$routeProviderReference.
			when('/:lang', { templateUrl: 'common/index/index.tpl.html', controller: 'IndexCtrl' }).
			when('/:lang/:option', {
				templateUrl: function(url) {
					if (!Language.getLanguage() || Language.getLanguage() !== url.lang) {
						$location.path( url.lang );
					} else {
						var urls = Menu.get();
						url.url     = 'common/' + url.option + '/' + url.option + '.tpl.html';
						tempCtrl 	= php.ucfirst(url.option) + 'Ctrl';
					}
					//console.log(tempCtrl);
					return url.url;
				},
				controller: tempCtrl
			}).
			
			when('/:lang/:option/:action', {
				templateUrl: function(url) {
					if (!Language.getLanguage() || Language.getLanguage() !== url.lang) {
						$location.path( url.lang );
					} else {               
						var urls = Menu.get();
						url.url     = 'common/' + url.option + '/' + url.option + '.' + url.action + '.tpl.html';
						tempCtrl 	= php.ucfirst(url.action) + 'Ctrl';
					}
					//console.log(tempCtrl);
					return url.url;
				},
				controller: tempCtrl
			}).
	
			otherwise({ redirectTo: '/nl/'});
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

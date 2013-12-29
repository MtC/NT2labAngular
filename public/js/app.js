angular.module('MtClab',['ngRoute','SecurityModule','ResourceModule','LanguageModule','NavigationModule','IndexModule','AppsModule','OptionsModule','UserModule','LoginModule']).
	
	config(function($routeProvider, $locationProvider) {
        $routeProviderReference = $routeProvider;
        $locationProvider.html5Mode(false);
        $locationProvider.hashPrefix('!');
    }).

	factory('menuFactory', function() {
		var menuFactory = {},
			menu = {};
		menuFactory.set = function(newMenu) {
			menu = newMenu;
		};
		menuFactory.get = function() {
			return menu;
		}
		return menuFactory;
	}).
	
	filter('searchFor', function(){
		return function(arr, searchString){
			if(!searchString){
				return arr;
			}
			var result = [];
			searchString = searchString.toLowerCase();
			console.log(searchString);
			angular.forEach(arr, function(item){
				if(item.name.toLowerCase().indexOf(searchString) !== -1){
					result.push(item);
				}
			});
			return result;
		};
	}).

	controller('LanguageCtrl',['$scope', '$location', function($scope, $location){
		$scope.go = function ( path ) {
			$location.path( path );
		};
	}]).

	run(['$rootScope', '$location', 'Token', 'menuFactory', 'Language', function($rootScope, $location, Token, menuFactory, Language) {
		var tempCtrl;
		
		$rootScope.$on( "$routeChangeStart", function(event, next, current) {
			var translations = menuFactory.get();
			if (typeof(translations.init) !== 'undefined') {
				if (typeof(next.params.option) !== 'undefined') {
					next.params.option = translations[next.params.option];
				}
				if (typeof(next.params.action) !== 'undefined') {
					next.params.action = translations[next.params.action];
				}
			} else {
				$location.path( 'nl' );
			}
		});
			
		$routeProviderReference.
			when('/:lang', { templateUrl: 'common/index/index.tpl.html', controller: 'IndexCtrl' }).
			when('/:lang/:option', {
				templateUrl: function(url) {
					if (!Language.getLanguage() || Language.getLanguage() !== url.lang) {
						$location.path( url.lang );
					} else {               
						var urls = menuFactory.get();
						url.url     = 'common/' + url.option + '/' + url.option + '.tpl.html';
						tempCtrl 	= php.ucfirst(url.option) + 'Ctrl';
					}
					console.log(tempCtrl);
					return url.url;
				},
				controller: tempCtrl
			}).
			
			when('/:lang/:option/:action', {
				templateUrl: function(url) {
					if (!Language.getLanguage() || Language.getLanguage() !== url.lang) {
						$location.path( url.lang );
					} else {               
						var urls = menuFactory.get();
						url.url     = 'common/' + url.option + '/' + url.option + '.' + url.action + '.tpl.html';
						tempCtrl 	= php.ucfirst(url.action) + 'Ctrl';
					}
					console.log(tempCtrl);
					return url.url;
				},
				controller: tempCtrl
			}).
	
			otherwise({ redirectTo: '/nl/'});
	}]);
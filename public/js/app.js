angular.module('MtClab',['ngRoute','IndexModule','AppsModule','OptionsModule','UserModule','LoginModule','pascalprecht.translate']).
	
	config(function($routeProvider, $locationProvider, $translateProvider) {
        $routeProviderReference = $routeProvider;
        $translateProviderReference = $translateProvider;
        $locationProvider.html5Mode(false);
        $locationProvider.hashPrefix('!');
    }).
	
	provider('language', function () {
		var sLanguage = 'nl';
		return {
			$get: function () {
				return {
					setLanguage: function (language) {
						sLanguage = language;
					},
					getLanguage: function () {
						return sLanguage;
					}
				}
			}
		}
	}).
	
	factory('Resource', ['$http', 'Token', 'XSRF', function ($http, Token, XSRF) {
		return function (rest) {
			var urlBase		= '/public/api/' + rest,
				urlId,
				Resource = function (data) {
					angular.extend(this, data);
				};
				
			Resource.setId = function (id) {
				urlId = id;
			}
				
			Resource.query = function () {
				return $http.get(urlBase, {
					headers: {'token': Token.get()}
				}).then(function (response) {
					Token.set(response.headers('token'));
					if (response.headers('X-XSRF-TOKEN')) XSRF.set(response.headers('X-XSRF-TOKEN'));
					return response;
				});
			};
				
			Resource.post = function (params) {
				return $http.post(urlBase, 
					JSON.stringify(params), {
					headers: {'token': Token.get(), 'X-XSRF-TOKEN': XSRF.get()}
				}).then(
					function (response) {
						Token.set(response.headers('token'));
						resp = response.data;
						resp.error = false;
						return resp;
					},
					function (response) {
						return {'error' : 'error.bad-request'};
					}
				);
			};
			
			Resource.put = function (params) {
				url = urlBase + '/' + urlId;
				return $http.put(url, 
					JSON.stringify(params), {
					headers: {'token': Token.get(), 'X-XSRF-TOKEN': XSRF.get()}
					
				}).then(
					function (response) {
						Token.set(response.headers('token'));
						return response;
					});
			};
			
			return Resource;
		}
	}]).

	factory('tokenHandler', function() {
		var tokenHandler = {},
			sToken;
		tokenHandler.set = function (newToken) {
			sToken = newToken;
		};
		tokenHandler.get = function () {
			return sToken;
		};	
		return tokenHandler;
	}).
	
	factory('Token', function() {
		var Token = {},
			sToken;
		Token.set = function (newToken) {
			sToken = newToken;
		};
		Token.get = function () {
			return sToken;
		};	
		return Token;
	}).
	
	factory('XSRF', function() {
		var XSRF = {},
			sXSRF = '';
		XSRF.set = function (newXSRF) {
			sXSRF = newXSRF;
		};
		XSRF.get = function () {
			return sXSRF;
		};	
		return XSRF;
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

	controller('LangCtrl',['$scope', '$location', function($scope, $location){
		$scope.go = function ( path ) {
			$location.path( path );
		};
	}]).

	run(['$rootScope', '$location', 'tokenHandler', 'menuFactory', 'language', function($rootScope, $location, tokenHandler, menuFactory, language) {
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
			$location.path( 'en' );
		}
	});
		
	$routeProviderReference.
        when('/:lang', { templateUrl: 'common/index/index.tpl.html', controller: 'IndexCtrl' }).
        when('/:lang/:option', {
            templateUrl: function(url) {
                if (!language.getLanguage() || language.getLanguage() !== url.lang) {
                    $location.path( url.lang );
                } else {               
                    var urls = menuFactory.get();
					url.url     = 'common/' + url.option + '/' + url.option + '.tpl.html';
					tempCtrl 	= php.ucfirst(url.option) + 'Ctrl';
                }
                return url.url;
            },
            controller: tempCtrl,
			resolve: {}
        }).
		
		when('/:lang/:option/:action', {
			templateUrl: function(url) {
				if (!language.getLanguage() || language.getLanguage() !== url.lang) {
                    $location.path( url.lang );
                } else {               
                    var urls = menuFactory.get();
					url.url     = 'common/' + url.option + '/' + url.option + '.' + url.action + '.tpl.html';
					tempCtrl 	= php.ucfirst(url.action) + 'Ctrl';
                }
                return url.url;			
			},
			controller: tempCtrl
		}).

        otherwise({ redirectTo: '/nl/'});
}]);

/*
mtclab.controller('appEdit', function($scope) {
    $scope.master = {};
    
    $scope.update = function(user) {
        $scope.master = angular.copy(user);
    };
});
*/

/*
mtclab.controller('PersonaCtrl', ['$scope', 'personaService', function ($scope, personaService) {
    $scope.loggedIn = false;
    $scope.login = function (response) {
        navigator.id.request({backgroundColor: '#c9eeff', 'siteName': 'NT2lab'});
    };
    $scope.logout = function () {
        navigator.id.logout();
    };
}]);
*/
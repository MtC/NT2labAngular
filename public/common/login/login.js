angular.module('LoginModule',[]).

    factory('Login', ['Resource', function (Resource) {
        return Resource('login');
    }]).

    controller('LoginCtrl',['$scope', '$location','Login', 'Language', 'Credentials', function($scope, $location, Login, Language, Credentials) {
        
        $scope.loginSubmit = function() {
            var user = $scope.user;
            Login.post({login: user.name, password: user.password}).then(
                function (response) {
					if (response.status === 400) {
						console.log('error');
					} else {
						if (Credentials.isAuthenticated()) {
							$location.path(Language.getLanguage());
						}
					}
                }
            );
        };
        
        $scope.isAuthenticated = function () {
            return Credentials.isAuthenticated();
        }
        
        $scope.isError = function () {
            return Credentials.isError();
        }
        
        $scope.getError = function () {
            return Credentials.getError();
        }
    }]);
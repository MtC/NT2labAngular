angular.module('LoginModule',[]).
    
    provider('Credentials', function () {
		var user  = false,
            error = false,
            xsrf, role;
		return {
			$get: function () {
				return {
                    setUser: function (newUser, newRole) {
                        user = newUser;
                        role = newRole;
                        this.setError(false);
                    },
                    getUser: function () {
                        return user;
                    },
                    getRole: function () {
                        return role;
                    },
                    isAuthenticated: function () {
                        return user ? true : false;
                    },
                    setError: function (newError) {
                        error = newError;
                    },
                    getError: function () {
                        return error;
                    },
                    isError: function () {
                        return error ? true : false;
                    }
				}
			}
		}
	}).
    
    factory('Login', ['Resource', function (Resource) {
        return Resource('login');
    }]).

    controller('LoginCtrl',['$scope', '$location','Login', 'Language', 'Credentials', function($scope, $location, Login, Language, Credentials) {
        $scope.loginSubmit = function() {
            
            var user = $scope.user;
            Login.post({login: user.name, password: user.password}).then(
                function (response) {
                    if (response.error) {
                        Credentials.setError(response.error);
                        console.log(Credentials.getError());
                    } else {
                        Credentials.setUser(response.data.user, response.data.role);
						// hier moet later meer komen
                        $location.path(Language.getLanguage());
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
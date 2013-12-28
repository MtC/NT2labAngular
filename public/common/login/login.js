angular.module('LoginModule',[]).
    controller('LoginCtrl',['$scope','$http','tokenHandler', function($scope, $http, tokenHandler) {
        console.log('LoginCtrl');
        $scope.loginSubmit = function() {
            var user = $scope.user;
            $http.post('api/login', {
                login: user.name,
                password: user.password
            }).success(function(json, status, headers, config) {
                console.log(headers('token'));
                tokenHandler.set(headers('token'));
            }).error(function(err) {
                // Alert if there's an error
                return alert(err.message || "Zijn alle velden ingevuld?");
            });
        };
    }]);
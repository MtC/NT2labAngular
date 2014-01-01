angular.module('NavigationModule', [/*'ResourceModule'*/]).

	factory('Logout', ['Resource', function (Resource) {
        return Resource('logout');
    }]).

    controller('NavigationCtrl', ['$scope', 'Credentials', '$location', 'Logout', function($scope, Credentials, $location, Logout) {
        $scope.isAuthenticated = function () {
            return Credentials.isAuthenticated();
        }
		
		$scope.logout = function () {
			Logout.post().then(function (response) {
				console.log(sessionStorage);
				Credentials.setUser(false, false);
				$location.path('/');
			});
		}
    }]);
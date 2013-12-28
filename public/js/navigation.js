angular.module('NavigationModule', [/*'ResourceModule'*/]).

    controller('NavigationCtrl', ['$scope', 'Credentials', function($scope, Credentials) {
        $scope.isAuthenticated = function () {
            return Credentials.isAuthenticated();
        }
		
		$scope.logout = function () {
			console.log('logout'); 
		}
    }]);
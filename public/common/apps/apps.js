angular.module('AppsModule', []).

    factory('Apps', ['$resource', function($resource){
        return $resource('api/app', {}, {
            query: {method:'GET', params:{}, isArray:true, headers: {'token': tokenHandler.get()}}
        });
    }]).

    controller('AppCtrl', ['$scope','$routeParams','App',function($scope, $routeParams, App) {
        console.log('AppCtrl');
        //$scope.apps = App.query({id:$routeParams.id});
    }]).
    
    controller('AppsCtrl', ['$scope', 'Apps',function($scope, Apps) {
        console.log('AppsCtrl');
        //$scope.apps = Apps.query();
    }]);
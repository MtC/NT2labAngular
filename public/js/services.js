var mtclabServices = angular.module('mtclabServices', ['ngResource']);

mtclabServices.factory('App', ['$resource','$routeParams', function($resource){
    return $resource('api/app/:id',{id: '@id'});
}]);

mtclabServices.factory('Lang', ['$resource', 'tokenHandler', function($resource, tokenHandler) {
    return $resource('api/lang/:lang',{lang: '@lang'}, { headers: {'token': tokenHandler.get()}});
}]);

mtclabServices.factory('ToDo',['$resource', 'tokenHandler', function($resource, tokenHandler) {
    return $resource('api/todo/:id',
        { id: '@id'},
        { headers: {'token': tokenHandler.get()}});
}]);
/*
mtclab.controller('LangCtrl',['$scope', '$location', function($scope, $location){
    $scope.go = function ( path ) {
        $location.path( path );
    };
}]);
*/
/*
mtclabServices.service('personaService', function personaService($http, $q) {

    navigator.id.watch({
        loggedInUser: null,
        onlogin: function (assertion) {
            var deferred = $q.defer();
            console.log('onlogin');

            $http.post("api/persona/verify", 
                {assertion:assertion},
                function(msg) { console.log(msg.success);})
                .then(function (response) {
                if (response.data.status !== "okay") {
                  deferred.reject(response.data.reason);
                } else {
                    console.log('ik ben er');
                  deferred.resolve(response.data.email);
                }
            });
      },
      onlogout: function () {
          window.location = '/logout';
      }
    });

    return {

    };

  });
*/
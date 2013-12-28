angular.module('IndexModule',[]).

    factory('Index', ['Resource', function (Resource) {
        return Resource('lang');
    }]).

    controller('IndexCtrl', ['$scope', '$http', '$route', '$translate', 'Token', 'menuFactory', 'Language', 'Index', function($scope, $http, $route, $translate, Token, menuFactory, Language, Index) {
    if (Language.getLanguage() !== $route.current.pathParams.lang) {
        console.log($route.current.pathParams.lang);
        Index.setId($route.current.pathParams.lang);
        Index.get().then(
            function (response) {
                $translateProviderReference.translations(response.data.lang, response.data);
                $scope.$on('$routeChangeSuccess', function() {
                    $translate.uses(response.data.lang);
                });
                menuFactory.set(response.data.urlCheck);
                Language.setLanguage(response.data.lang);
                $translate.uses(Language.getLanguage());
            }
        );
    }
}]);
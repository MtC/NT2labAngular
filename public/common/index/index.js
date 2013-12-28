angular.module('IndexModule',[]).
    controller('IndexCtrl', ['$scope', '$http', '$route', '$translate', 'tokenHandler', 'menuFactory', 'language', function($scope, $http, $route, $translate, tokenHandler, menuFactory, language) {
    if (language.getLanguage() !== $route.current.pathParams.lang) {
        $http.get('api/lang/' + $route.current.pathParams.lang, { headers: {'token': tokenHandler.get()}})
        .success(function(langs, status, headers, config) {
            tokenHandler.set(headers('token'));
            //console.log(headers('token'));
            $translateProviderReference.translations(langs.lang, langs);
            $scope.$on('$routeChangeSuccess', function() {
                $translate.uses(langs.lang);
            });
            menuFactory.set(langs.urlCheck);
            language.setLanguage(langs.lang);
            $translate.uses(language.getLanguage());
        });
    }
}]);
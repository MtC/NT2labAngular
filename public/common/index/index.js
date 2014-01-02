angular.module('IndexModule',[]).

    factory('Index', ['Resource', function (Resource) {
        return Resource('lang');
    }]).
    
    factory('Trial', ['Resource', function (Resource) {
        return Resource('trial');
    }]).

    controller('IndexCtrl', ['$scope', '$route', '$translate', 'Trial', 'Language', 'Index', 'Credentials', function($scope, $route, $translate, Trial, Language, Index, Credentials) {
        console.log('indexpagina bereikt');
        /*
        if (Language.getLanguage() !== $route.current.pathParams.lang || Language.isReload()) {
            console.log('t');
            console.log(Index.getHeaders());
            console.log(sessionStorage);
            Credentials.setTokens('pipo', 'clown');
            console.log(Index.getHeaders());
            console.log(sessionStorage);
            Index.setHeaders({token: 'zotteklap'});
            console.log(Index.getHeaders());
            console.log(sessionStorage);
            Index.setId($route.current.pathParams.lang);
            Index.get().then(
                function (response) {
                    $translateProviderReference.translations(response.data.lang, response.data);
                    //console.log(response.data);
                    if (Language.isSessionStorage()) sessionStorage.setItem('lang', response.data.lang);
                    $scope.$on('$routeChangeSuccess', function() {
                        $translate.uses(response.data.lang);
                    });
                    Menu.set(response.data.urlCheck);
                    Language.setLanguage(response.data.lang);
                    Language.setUrl(response.data.url);
                    $translate.uses(Language.getLanguage());
                }
            );
        }
        */
        
        $scope.authenticate = function () {
            Trial.setId('pixie');
            Trial.query().then(function (response) {
                if (response.headers('request') && response.headers('request') === 'credentials') {
                    Trial.setCredentials(true);
                    console.log(Trial.getCredentials());
                    Trial.query().then(function (response) {
                        console.log(response);
                        
                    });
                }
            });
            /*
            Trial.setId('pixie');
            Trial.get().then(function (response) {
                if (response.headers('request') && response.headers('request') === 'credentials') {
                    Trial.setCredentials(true);
                    console.log(Trial.getCredentials());
                    Trial.get().then(function (response) {
                        console.log(response);
                        
                    });
                }
            });
            */
        }
    }]);
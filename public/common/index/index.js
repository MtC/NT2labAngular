angular.module('IndexModule',[]).

    factory('Index', ['Resource', function (Resource) {
        return Resource('lang');
    }]).
    
    factory('Trial', ['Resource', function (Resource) {
        return Resource('trial');
    }]).

    controller('IndexCtrl', ['$scope', '$route', '$translate', 'Trial','Token', 'XSRF', 'Menu', 'Language', 'Index', 'Credentials', function($scope, $route, $translate, Trial, Token, XSRF, Menu, Language, Index, Credentials) {
        //if (!Language.getLanguage() && window.sessionStorage && sessionStorage.getItem('lang')) Language.setLanguage(sessionStorage.getItem('lang'));
        if (Language.getLanguage() !== $route.current.pathParams.lang || Language.isReload()) {
            //console.log($route.current.pathParams.lang);
            //console.log(Index.setOption());
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
                    console.log(sessionStorage);
                    console.log(XSRF.get());
                }
            );
        }
        
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
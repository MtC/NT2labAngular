angular.module('SecurityModule',[]).

    config(['$httpProvider', function ($httpProvider) {
        $httpProvider.responseInterceptors.push('securityInterceptor');
    }]).
    
    provider('securityAuthorization', {
        requireAdminUser: ['securityAuthorization', function(securityAuthorization) {
            return securityAuthorization.requireAdminUser();
        }],
        requireAuthenticatedUser: ['securityAuthorization', function(securityAuthorization) {
            return securityAuthorization.requireAuthenticatedUser();
        }],
        $get: ['security', 'securityRetryQueue', function(security, queue) {
            var service = {
                requireAuthenticatedUser: function() {
                    var promise = security.requestCurrentUser().then(function(userInfo) {
                        if ( !security.isAuthenticated() ) {
                            return queue.pushRetryFn('unauthenticated-client', service.requireAuthenticatedUser);
                        }
                    });
                    return promise;
                },
                requireAdminUser: function() {
                    var promise = security.requestCurrentUser().then(function(userInfo) {
                        if ( !security.isAdmin() ) {
                            return queue.pushRetryFn('unauthorized-client', service.requireAdminUser);
                        }
                    });
                    return promise;
                }
            };
            return service;
        }]
    }).
    
    factory('securityInterceptor', ['$injector', 'securityRetryQueue', function ($injector, queue) {
        return function (promise) {
            return promise.then(null, function (originalResponse) {
                if (originalResponse.status === 401) {
                    promise = queue.pushRetryFn('unauthorized-server', function retryRequest () {
                        return $injector.get('$http')(originalResponse.config);    
                    });
                }
                return promise;
            });
        }
    }]).
    
    factory('securityRetryQueue', ['$q', '$log', function($q, $log) {
        var retryQueue = [],
            service = {
                onItemAddedCallbacks: [],
                hasMore: function() {
                    return retryQueue.length > 0;
                },
                push: function(retryItem) {
                    retryQueue.push(retryItem);
                    angular.forEach(service.onItemAddedCallbacks, function(cb) {
                        try {
                            cb(retryItem);
                        } catch(e) {
                            $log.error('securityRetryQueue.push(retryItem): callback threw an error' + e);
                        }
                    });
                },
                pushRetryFn: function(reason, retryFn) {
                    if ( arguments.length === 1) {
                        retryFn = reason;
                        reason = undefined;
                    }
                    var deferred = $q.defer(),
                        retryItem = {
                            reason: reason,
                            retry: 
                                function () {
                                    $q.when(retryFn()).then(function (value) {
                                        deferred.resolve(value);
                                    }, 
                                    function (value) {
                                        deferred.reject(value);
                                    });
                                },
                                cancel: function () {
                                    deferred.reject();
                                }
                        };
                    service.push(retryItem);
                    return deferred.promise;
                },
                retryReason: function () {
                    return service.hasMore() && retryQueue[0].reason;
                },
                cancelAll: function () {
                    while(service.hasMore()) {
                        retryQueue.shift().cancel();
                    }
                },
                retryAll: function () {
                    while(service.hasMore()) {
                        retryQueue.shift().retry();
                    }
                }
            };
        return service;
    }]).
    
    factory('security', ['$http', '$q', '$location', 'securityRetryQueue', '$dialog', function($http, $q, $location, queue, $dialog) {
        function redirect(url) {
            url = url || '/';
            $location.path(url);
        }
        var loginDialog = null;
        function openLoginDialog() {
            if ( loginDialog ) {
                throw new Error('Trying to open a dialog that is already open!');
            }
            loginDialog = $dialog.dialog();
            // opletten: url aanpassen
            loginDialog.open('security/login/form.tpl.html', 'LoginFormController').then(onLoginDialogClose);
        }
        function closeLoginDialog(success) {
            if (loginDialog) {
                loginDialog.close(success);
            }
        }
        function onLoginDialogClose(success) {
            loginDialog = null;
            if ( success ) {
                queue.retryAll();
            } else {
                queue.cancelAll();
                redirect();
            }
        }
        queue.onItemAddedCallbacks.push(function(retryItem) {
            if ( queue.hasMore() ) {
                service.showLogin();
            }
        });
        var service = {
            getLoginReason: function() {
                return queue.retryReason();
            },
            showLogin: function() {
                openLoginDialog();
            },
            login: function(email, password) {
                // opletten: url aanpassen
                var request = $http.post('/login', {email: email, password: password});
                return request.then(function(response) {
                    service.currentUser = response.data.user;
                    if ( service.isAuthenticated() ) {
                        closeLoginDialog(true);
                    }
                    return service.isAuthenticated();
                });
            },
            cancelLogin: function() {
                closeLoginDialog(false);
                redirect();
            },
            logout: function(redirectTo) {
                // opletten: url aanpassen
                $http.post('/logout').then(function() {
                    service.currentUser = null;
                    redirect(redirectTo);
                });
            },
            requestCurrentUser: function() {
                if ( service.isAuthenticated() ) {
                    return $q.when(service.currentUser);
                } else {
                    // opletten: url aanpassen
                    return $http.get('/current-user').then(function(response) {
                        service.currentUser = response.data.user;
                        return service.currentUser;
                    });
                }
            },
            currentUser: null,
            isAuthenticated: function(){
                return !!service.currentUser;
            },
            isAdmin: function() {
                return !!(service.currentUser && service.currentUser.admin);
            }
        };
        return service;
    }]);
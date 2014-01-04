angular.module('TokenModule',[]).

    provider('Credentials', function () {
		var bSessionStorage = false,
			oErrors			= {
				bError:	false,
				sError:	''
			},
            oCredentials    = {
                token:  false,
                xsrf:   false,
                user:   false,
                role:   false,
                save:   function () {
                    if (bSessionStorage) {
                        sessionStorage.setItem('credentials',JSON.stringify(oCredentials));
                    }
                }
            };
		return {
			setBoolSessionStorage: function (bool) {
				bSessionStorage = bool;
			},
            setTokensSessionStorage: function () {
                if (sessionStorage.getItem('credentials')) {
                    var credentials = JSON.parse(sessionStorage.getItem('credentials'));
                    oCredentials.token  = credentials.token ? credentials.token : false;
                    oCredentials.xsrf   = credentials.xsrf ? credentials.xsrf : false;
					oCredentials.user   = credentials.user ? credentials.user : false;
					oCredentials.role   = credentials.role ? credentials.role : false;
                } else {
					sessionStorage.setItem('credentials', JSON.stringify(oCredentials));
				}
            },
            getCredentials: function () {
                return oCredentials;
            },
			$get: function () {
				return {
					isSessionStorage: function () {
						return bSessionStorage;
					},
                    setUser: function (user, role) {
                        oCredentials.user = user;
                        oCredentials.role = role;
                    },
                    setTokens: function (token, xsrf) {
                        oCredentials.token   = token || false;
                        oCredentials.xsrf    = xsrf || false;
                    },
                    getHeaders: function () {
                        var headers = {
							token:	oCredentials.token,
							xsrf:	oCredentials.xsrf
						};
                        return headers;
                    },
                    isAuthenticated: function () {
                        return oCredentials.user ? true : false;
                    },
					isError: function () {
						return oErrors.bError;
					},
					getError: function () {
						return this.isError() ? oErrors.sError : '';
					},
					saveToSession: function () {
						if (bSessionStorage) {
							sessionStorage.setItem('credentials',JSON.stringify(oCredentials))
						}
					},
					getCredentials: function () {
						return oCredentials;
					}
				};
			}
		}; 
	}).
    
    config(function (CredentialsProvider) {
        if (window.sessionStorage) {
            CredentialsProvider.setBoolSessionStorage(true);
            CredentialsProvider.setTokensSessionStorage();
        }
    });
angular.module('TokenModule',[]).

    provider('Credentials', function () {
		var bSessionStorage = false,
            oCredentials     = {
                token:  false,
                xsrf:   false,
                user:   false,
                role:   false,
                save:   function () {
                    if (bSessionStorage) {
                        sessionStorage.setItem('credentials',JSON.stringify(oCredentials))
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
                    oCredentials.token   = credentials.token ? credentials.token : false;
                    oCredentials.xsrf   = credentials.xsrf ? credentials.xsrf : false;
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
                        oCredentials.token   = token;
                        oCredentials.xsrf    = xsrf || false;
                    },
                    getHeaders: function () {
                        var headers = {token: oCredentials.token}
                        if (oCredentials.xsrf !== 'null') {
                            headers.xsrf = oCredentials.xsrf;
                        }
                        return headers;
                    },
                    isAuthenticated: function () {
                        return true;
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
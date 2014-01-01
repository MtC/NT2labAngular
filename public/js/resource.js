/*
http://closure-compiler.appspot.com/
*/
angular.module('ResourceModule',[]).
	
	provider('Headers', function () {
		var oCredentials = {};
		return {
			setCredentials: function (credentials) {
				// credentials is een module met  de benodigde tokens en de save()-method.
				oCredentials = credentials;
			},
			$get: function () {
				return {
					getHeaders: function () {
						var headers = { token: oCredentials.token };
						if (oCredentials.xsrf !== 'null') headers.xsrf = oCredentials.xsrf;
						return headers;
					},
					setHeaders: function (headers) {
						oCredentials.token   = headers.token;
                        oCredentials.xsrf    = headers.xsrf || false;
						oCredentials.save();
					}
				}
			}
		}
	}).

    factory('Resource', ['$http', 'Token', 'XSRF', 'Headers', function ($http, Token, XSRF, Headers) {
		return function (rest) {
			var urlBase		= '/public/api/' + rest,
				urlId,
				Resource = function (data) {
					angular.extend(this, data);
				};
			Resource.getHeaders = function () {
				return Headers.getHeaders();
			}
			
			Resource.setHeaders = function (headers) {
				Headers.setHeaders(headers);
			}
				
			Resource.setId = function (id) {
				urlId = id;
			}
				
			Resource.query = function () {
				return $http.get(
                    urlBase, {
					headers: this.getHeaders()
				}).then(function (response) {
					Token.set(response.headers('token'));
                    console.log(response);
					return response;
				});
			};
            
            Resource.get = function (params) {
                url = urlBase + '/' + urlId;
				return $http.get(
                    url, 
                    JSON.stringify(params), {
					headers: {'token': Token.get()}
				}).then(function (response) {
					if (response.headers('request') && response.headers('request') === 'credentials') {
						console.log('credentials asked');
					}
					Token.set(response.headers('token'));
					if (response.headers('X-XSRF-TOKEN')) { 
                        XSRF.set(response.headers('X-XSRF-TOKEN'));
					}
					return response;
				});
			};
				
			Resource.post = function (params) {
				return $http.post(
                    urlBase,
					JSON.stringify(params), {
					headers: {'token': Token.get(), 'X-XSRF-TOKEN': XSRF.get()}
				}).then(
					function (response) {
						Token.set(response.headers('token'));
						if (response.headers('X-XSRF-TOKEN')) { 
							XSRF.set(response.headers('X-XSRF-TOKEN'));
						}
						resp = response;
						resp.error = false;
						return resp;
					},
					function (response) {
						return {'error' : 'error.bad-request'};
					}
				);
			};
			
			Resource.put = function (params) {
				url = urlBase + '/' + urlId;
				return $http.put(url, 
					JSON.stringify(params), {
					headers: {'token': Token.get(), 'X-XSRF-TOKEN': XSRF.get()}
					
				}).then(
					function (response) {
						Token.set(response.headers('token'));
						return response;
					});
			};
			
			return Resource;
		}
	}]).
	
	factory('Token', function() {
		var Token = {},
			sToken;
		//if (window.sessionStorage && sessionStorage.getItem('token')) sToken = sessionStorage.getItem('token');
		Token.set = function (newToken) {
			sToken = newToken;
			if (window.sessionStorage) sessionStorage.setItem('token', newToken);
		};
		Token.get = function () {
			return sToken;
		};	
		return Token;
	}).
	
	factory('XSRF', function() {
		var XSRF = {},
			sXSRF;
		//if (window.sessionStorage && sessionStorage.getItem('xsrf')) sXSRF = sessionStorage.getItem('xsrf');
		XSRF.set = function (newXSRF) {
			sXSRF = newXSRF;
			if (window.sessionStorage) {
				sessionStorage.setItem('xsrf', newXSRF);
			}
		};
		XSRF.get = function () {
			if (sXSRF === "false") sXSRF = false;
			return sXSRF;
		};	
		return XSRF;
	});

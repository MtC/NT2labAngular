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
						var headers = {};
						if (oCredentials.token) headers.token = oCredentials.token;
						if (oCredentials.xsrf) headers['X-XSRF-TOKEN'] = oCredentials.xsrf;
						return headers;
					},
					setHeaders: function (headers) {
						oCredentials.token   = headers('token') || false;
                        oCredentials.xsrf    = headers('X-XSRF-TOKEN') || false;
						oCredentials.save();
					}
				}
			}
		}
	}).

    factory('Resource', ['$http', 'Headers', function ($http, Headers) {
		return function (rest) {
			var urlBase		= '/public/api/' + rest,
				urlId		= false,
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
					headers: Resource.getHeaders()
				}).then(
					function (response) {
						Resource.setHeaders(response.headers);
						return response;
					},
					function (response) {
						
					}
				);
			};
            
            Resource.get = function (params) {
                url = urlBase + '/' + urlId;
				return $http.get(
                    url, 
                    JSON.stringify(params), {
					headers: Resource.getHeaders()
				}).then(
					function (response) {
						Resource.setHeaders(response.headers);
						return response;
					},
					function (response) {
						return response;
					}
				);
			};
				
			Resource.post = function (params) {
				return $http.post(
                    urlBase,
					JSON.stringify(params), {
					headers: Resource.getHeaders()
				}).then(
					function (response) {
						Resource.setHeaders(response.headers);
						return response;
						return resp;
					},
					function (response) {
						return {'error' : 'error.bad-request'};
					}
				);
			};
			
			Resource.put = function (params) {
				url = urlBase + '/' + urlId;
				return $http.put(
					url, 
					JSON.stringify(params), {
					headers: Resource.getHeaders()	
				}).then(
					function (response) {
						Resource.setHeaders(response.headers);
						return response;
					},
					function (response) {
						return response;
					}
				);
			};
			
			return Resource;
		}
	}]);
	/*
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
	*/

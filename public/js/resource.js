angular.module('ResourceModule',[]).

    factory('Resource', ['$http', 'Token', 'XSRF', function ($http, Token, XSRF) {
		return function (rest) {
			var urlBase		= '/public/api/' + rest,
				urlId,
				Resource = function (data) {
					angular.extend(this, data);
				};
				
			Resource.setId = function (id) {
				urlId = id;
			}
				
			Resource.query = function () {
				return $http.get(
                    urlBase, {
					headers: {'token': Token.get()}
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
					Token.set(response.headers('token'));
                    //console.log(response);
					if (response.headers('X-XSRF-TOKEN')) { 
                        XSRF.set(response.headers('X-XSRF-TOKEN'));
                        //console.log(response.headers('X-XSRF-TOKEN'));
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
		Token.set = function (newToken) {
			sToken = newToken;
		};
		Token.get = function () {
			return sToken;
		};	
		return Token;
	}).
	
	factory('XSRF', function() {
		var XSRF = {},
			sXSRF;
		XSRF.set = function (newXSRF) {
			sXSRF = newXSRF;
		};
		XSRF.get = function () {
			return sXSRF;
		};	
		return XSRF;
	});
angular.module('LanguageModule', ['pascalprecht.translate']).

    config(function($translateProvider) {
        $translateProviderReference = $translateProvider;
    }).
    
    provider('Language', function () {
		var sLanguage = '';
		return {
			$get: function () {
				return {
					setLanguage: function (language) {
						sLanguage = language;
					},
					getLanguage: function () {
						return sLanguage;
					}
				}
			}
		}
	});
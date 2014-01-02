angular.module('LanguageModule', ['pascalprecht.translate']).
    
    provider('Language', function () {
		var sLanguage 	= false,
			oUrl		= {},
			bSessionStorage = false,
			bLanguageInSession = false,
			bReload = false;
		return {
			setLanguage: function (lang) {
				sLanguage = lang;
			},
			isLanguageInSession: function (bool) {
				bLanguageInSession = bool;
			},
			isSessionStorage: function (bool) {
				bSessionStorage = bool;
			},
			$get: function () {
				return {
					setLanguage: function (language) {
						sLanguage = language;
					},
					getLanguage: function () {
						return sLanguage;
					},
					isLanguageInSession: function () {
						return bLanguageInSession;
					},
					setUrl: function (url) {
						oUrl = url;
					},
					getUrl: function (url) {
						return oUrl[url];
					},
					setSessionStorage: function (bool) {
						bSessionStorage = bool;
					},
					isSessionStorage: function () {
						return bSessionStorage;
					},
					setReload: function (bool) {
						bReload = bool;
					},
					isReload: function () {
						return bReload;
					}
				}
			}
		}
	}).
	
	config(function($translateProvider, LanguageProvider) {
        $translateProviderReference = $translateProvider;
		if (window.sessionStorage) {
			LanguageProvider.isSessionStorage(true);
			if (sessionStorage.getItem('langs')) {
				var langs = sessionStorage.getItem('langs');
				LanguageProvider.setLanguage(langs.lang);
				LanguageProvider.isLanguageInSession(true);
				console.log('language in session');
			} else {
				LanguageProvider.setLanguage('nl');
				console.log('language must be loaded');
			}
		} else {
			LanguageProvider.setLanguage('nl');
		}
    });
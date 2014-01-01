angular.module('LanguageModule', ['pascalprecht.translate']).
    
    provider('Language', function () {
		var sLanguage 	= false,
			oUrl		= {},
			bSessionStorage = false,
			bReload = false;
		return {
			setLanguage: function (lang) {
				sLanguage = lang;
				console.log(sessionStorage);
				console.log(sLanguage);
			},
			$get: function () {
				return {
					setLanguage: function (language) {
						sLanguage = language;
						if (window.sessionStorage) {
							sessionStorage.setItem('lang',sLanguage);
						}
					},
					getLanguage: function () {
						return sLanguage;
					},
					setUrl: function (url) {//waarom bij set alles en bij get een method?
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
		var lang = window.sessionStorage && sessionStorage.getItem('lang') ? sessionStorage.getItem('lang') : false;
		LanguageProvider.setLanguage(lang);
    }).
	
	factory('Menu', function() {
		var menuFactory = {},
			menu = {};
		menuFactory.set = function(newMenu) {
			menu = newMenu;
		};
		menuFactory.get = function() {
			return menu;
		}
		return menuFactory;
	});
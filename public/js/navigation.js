angular.module('NavigationModule', []).
	
	provider('Navigation', [function () {
		var bSessionStorage = false,
			bMenuInSession	= false,
			oMenu			= {},
			sLanguage		= '';
		return {
			setMenu: function (menu) {
				oMenu = JSON.parse(menu);
			},
			isMenuInSession: function (bool) {
				bMenuInSession = bool;
			},
			isSessionStorage: function (bool) {
				bSessionStorage = bool;
			},
			$get: ['$location', function ($location) {
				return {
					setMenu: function (menu) {
						oMenu = menu;
						if (bSessionStorage) sessionStorage.setItem('menu', JSON.stringify(menu));
					},
					setLanguage: function (language) {
						sLanguage = language;
					},
					getLanguage: function () {
						return sLanguage;
					},
					getItem: function (item) {
						return oMenu.urlToMenu[oMenu.languageToUrl[sLanguage][item]];
					},
					isMenuLoaded: function () {
						return bMenuInSession;
					},
					getMenu: function () {
						return oMenu;
					},
					getMenuItem: function (item) {
						return oMenu.urlToLanguage[sLanguage][item];
					},
					go: function (path) {
						console.log(path);
						$location.path(path);
					}
				}
			}]
		}
	}]).
	
	config(function(NavigationProvider) {
		if (window.sessionStorage) {
			NavigationProvider.isSessionStorage(true);
			if (sessionStorage.getItem('menu')) {
				var menu = sessionStorage.getItem('menu');
				NavigationProvider.isMenuInSession(true);
				NavigationProvider.setMenu(menu);
			}
		}
    });
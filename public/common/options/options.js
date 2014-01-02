/**
 *  Module hoort bij MtClab: MtClab heeft de benodigde ResourceModule, die van $http een soort $resource maakt
 *  Daarnaast heeft MtClab nu nog de LoginModule: deze moet worden aangepast, maar heeft de Credentials
 */
angular.module('OptionsModule',[]).

    factory('ToDo', ['Resource', function (Resource) {
        return Resource('todo');
    }]).
    
    controller('OptionsCtrl', ['$scope', 'Credentials', function ($scope, Credentials) {
        $scope.isAuthenticated = function () {
            return Credentials.isAuthenticated();
        }
    }]).
    
    controller('TodoCtrl', ['$scope', 'ToDo', 'Navigation', function ($scope, ToDo, Navigation) {
        $scope.todos = [];
        $scope.toggled = [];
        $scope.sortField = 'done_by';
        $scope.todoAddSubmit = true;
        $scope.toDo = {priority: false};
        
        $scope.isToggledTodo = function (index) {
            return $scope.toggled[index];
        }
        
        $scope.toggleTodo = function (index) {
            $scope.toggled[index] = !$scope.toggled[index];
        }
        
        $scope.sortBy = function (fieldName) {
            if ($scope.sortField === fieldName) {
                $scope.sortFieldReverse = !$scope.sortFieldReverse;
            } else {
                $scope.sortField = fieldName;
                $scope.sortFieldReverse = false;
            }
        }
		
		$scope.dater = function () {
			// simpele oplossing: kan en moet beter
			if (Navigation.getLanguage() === 'nl') {
				return 'dd-MM-yyyy';
			} else {
				return 'yyyy-MM-dd';
			}
		}
        
        $scope.checkboxed = function () {
            $scope.toDo.priority = !$scope.toDo.priority
        }
        
        $scope.resetForm = function () {
            $scope.toDo = {priority: false};
            $scope.formTodo.$setPristine()
        }
        
        $scope.go = function ( path ) {
			Navigation.go( path );
		};
        
        ToDo.query().then(function (todos) {
            $scope.todos = todos.data;
        });
       
        $scope.changeTodo = function (todo) {
			console.log('test');
			Navigation.go(Navigation.getLanguage() + '/' + Navigation.getMenuItem('menu.options.url') + '/' + Navigation.getMenuItem('options.todo.change-todo'));
		};
        
        $scope.addTodo = function (todo) {
            console.log(todo);
            ToDo.post(todo).then(function (todo) {
                console.log(todo);
                //$scope.todos.push(todo.data[0]);
                //$scope.todoAddSubmit = false;
				Navigation.go(Navigation.getLanguage() + '/' + Navigation.getMenuItem('menu.options.url') + '/' + Navigation.getMenuItem('options.todo.url'));
            });
        };
        
        $scope.todoOnOff = function (todo, action) {
            ToDo.setId(todo.id);
            if (action === 'removed') {
                ToDo.put({ action: 'removed'}).then(function (response) {
                    $scope.todos.splice($scope.todos.indexOf(todo), 1);
                });
            } else if (action === 'priority') {
                ToDo.put({ action: 'priority'}).then(function (response) {
                    $scope.todos[$scope.todos.indexOf(todo)].priority = response.data.priority;
                });
            } else if (action === 'done') {
                ToDo.put({ action: 'done'}).then(function (response) {
                    $scope.todos[$scope.todos.indexOf(todo)].done = response.data.done;
                });
            }
        };

        $scope.canSave = function () {
            return $scope.formTodo.$valid;
        };
    }]).
	
	controller('LanguageCtrl',['$scope', 'Navigation', function ($scope, Navigation) {
		$scope.go = function (lang) {
			Navigation.setLanguage(lang);
			console.log('/' + lang + '/' + Navigation.getMenuItem('menu.options.url') + '/' + Navigation.getMenuItem('options.todo.url'));
			Navigation.go('/' + lang + '/' + Navigation.getMenuItem('menu.options.url') + '/' + Navigation.getMenuItem('options.language.url'));
		}
	}]);
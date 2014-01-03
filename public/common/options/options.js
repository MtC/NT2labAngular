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
        
        $scope.go = function ( path ) {
			Navigation.go( path );
		};
        
        ToDo.query().then(function (todos) {
            $scope.todos = todos.data;
        });
       
        $scope.changeTodo = function (todo) {
			console.log(todo.id);
			Navigation.go(Navigation.getLanguage() + '/' + Navigation.getMenuItem('menu.options.url') + '/' + Navigation.getMenuItem('todo.form-change.url') + '/' + todo.id);
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
    }]).
	
	controller('LanguageCtrl',['$scope', 'Navigation', function ($scope, Navigation) {
		$scope.go = function (lang) {
			Navigation.setLanguage(lang);
			console.log('/' + lang + '/' + Navigation.getMenuItem('menu.options.url') + '/' + Navigation.getMenuItem('options.todo.url'));
			Navigation.go('/' + lang + '/' + Navigation.getMenuItem('menu.options.url') + '/' + Navigation.getMenuItem('options.language.url'));
		}
	}]).
	
	controller('formTodoCtrl',['$scope', '$routeParams', 'ToDo', 'Navigation', function ($scope, $routeParams, ToDo, Navigation) {
		$scope.toDo = {priority: false};
		
		console.log($routeParams);
		
		if ($routeParams.id) {
			$scope.todoAddSubmit = false;
			ToDo.setId($routeParams.id);
			ToDo.get().then(function (response) {
				if (response.status !== 200) {
					$scope.error = true;
				} else {
					$scope.error = false;
					var td = response.data[0];
					$scope.toDo = {
						id:			td.id,
						todo:		td.name,
						doneBy:		td.done_by,
						description:td.description,
						priority:	td.priority === '0' ? false : true
					}
					$scope.td = angular.copy($scope.toDo);
				}
			});
		} else {
			$scope.todoAddSubmit = true;
		}
		
		$scope.checkboxed = function () {
            $scope.toDo.priority = !$scope.toDo.priority
        }
		
		$scope.resetForm = function () {
            $scope.toDo = {priority: false};
            if ($scope.td) {
				$scope.toDo = {
					todo:		$scope.td.todo,
					doneBy:		$scope.td.doneBy,
					description:$scope.td.description,
					priority:	$scope.td.priority
				}
            } else {
				$scope.formTodo.$setPristine();
            }
        }
		
		$scope.addTodo = function (todo) {
            ToDo.post(todo).then(function (todo) {
				Navigation.go(Navigation.getLanguage() + '/' + Navigation.getMenuItem('menu.options.url') + '/' + Navigation.getMenuItem('options.todo.url'));
            });
        };
		
		$scope.changeTodo = function (todo) {
			ToDo.setId(todo.id);
            ToDo.put(todo).then(function (response) {
				console.log(response);
				Navigation.go(Navigation.getLanguage() + '/' + Navigation.getMenuItem('menu.options.url') + '/' + Navigation.getMenuItem('options.todo.url'));
            });
        };
		
		$scope.canSave = function () {
            return $scope.formTodo.$valid;
        };
	}]);
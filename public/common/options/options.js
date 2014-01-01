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
    
    controller('TodoCtrl', ['$scope', '$location', 'ToDo', 'Language', function ($scope, $location, ToDo, Language) {
        $scope.todos = [];
        $scope.toggled = [];
        $scope.sortField = 'name';
        $scope.reverse = false;
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
                $scope.reverse = !$scope.reverse;
            } else {
                $scope.sortField = fieldName;
                $scope.reverse = false;
            }
        }
		
		$scope.doneFilter = function (todo) {
			return todo.done === 1;
		}
		
		$scope.dater = function () {
			return 'd-MM-yyyy';
		}
        
        $scope.checkboxed = function () {
            $scope.toDo.priority = !$scope.toDo.priority
        }
        
        $scope.resetForm = function () {
            $scope.toDo = {priority: false};
            $scope.formTodo.$setPristine()
        }
        
        $scope.go = function ( path ) {
			$location.path( path );
		};
        
        ToDo.query().then(function (todos) {
            $scope.todos = todos.data;
        });
       
        $scope.changeTodo = function (todo) {
			console.log('test');
			$location.path(Language.getUrl('lang') + '/' + Language.getUrl('menu.options.url') + '/' + Language.getUrl('todo.form.url'));
		};
        
        $scope.addTodo = function (todo) {
            console.log(todo);
            ToDo.post(todo).then(function (todo) {
                console.log(todo);
                //$scope.todos.push(todo.data[0]);
                //$scope.todoAddSubmit = false;
				$location.path(Language.getUrl('lang') + '/' + Language.getUrl('menu.options.url') + '/' + Language.getUrl('options.todo.url'));
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
        
    }]);
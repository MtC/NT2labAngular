angular.module('OptionsModule',['MtClab']).

    factory('ToDo', ['Resource', function (Resource) {
        return Resource('todo');
    }]).
    
    controller('OptionsCtrl', []).
    controller('TodoCtrl', [ '$scope', 'ToDo', '$http', 'tokenHandler', function($scope, ToDo, $http, tokenHandler) {
        $scope.todos = [];
        
        ToDo.query().then(function (todos) {
            $scope.todos = todos.data;
        });
        
        $scope.addTodo = function (name, description, doneBy, priority) {
            console.log(name +':' + description + ':' + doneBy + ':' + priority);
            /*
            ToDo.add({name: name}).then(function (todo) {
                $scope.todos.push(todo.data[0]);
            });
            */
        };
        
        $scope.todoOnOff = function (todo, action) {
            ToDo.setId(todo.id);
            if (action === 'removed') {
                ToDo.put({ action: 'removed'}).then(function (todo) {
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
        }]);
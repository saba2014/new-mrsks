myApp.controller('Shedule', function ($scope, $http, $timeout) {
    $scope.button = "Сохранить";
    $scope.log = "admin/loginfo";
    $http.get('admin/config')
            .then(function (res) {
                $scope.data = res.data;
            });

    $scope.save = function (data) {
        $http({
            method: 'POST',
            url: 'admin/save',
            file_name: 'file_name= import_config.xml',
            data: "data=" + data,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        }).success(function (data) {
        });
    };

    $scope.onChange = function () {
        $scope.button = "Сохранить";
    };
});


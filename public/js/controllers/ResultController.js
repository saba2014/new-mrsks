var getObjs, setItems, setItemsWithoutDigest, setItemsCount,clickFirstItem;
window.myApp.controller("ResultController", ['$http', '$scope', '$sce', function ($http, $scope, $sce) {
    $scope.itemPerPage = 30;
    $scope.page = 1;
    $scope.collection = '';
    $scope.item_count = 0;
    $scope.items = [];

    getObjs = function (flag = 1) {
        if ($scope.collection !== document.getElementById('s_Select').value) {
            $scope.collection = document.getElementById('s_Select').value;
            $scope.page = 1;
        }
        if
        ($scope.collection === "tplnr" || $scope.collection === "denotation") {
            flag = 0;
        }
        $("#p_Result").hide();
        $("#spin").spin();
        $scope.items = [];
        geocode($scope.page, $scope.itemPerPage, flag);
    };
    $scope.pageChanged = function (newPageNumber) {
        $scope.page = newPageNumber;
        getObjs();
    };
    setItems = function (items, flag = 0) {
        if (flag === 1) {
            if (items.content.length === undefined || typeof(items.content) === 'string') {
                if (typeof(items.content) !== 'string') {
                    $scope.items[0] = items.content[0];
                }
                else {
                    $scope.items[0] = items.content;
                }
                $scope.item_count = 1;
            }
            else {
                $scope.items = items.content;
                $scope.item_count = items.content.length;
            }

            $scope.$digest();
        }
        else {
            if (typeof (items.content) === 'string') {
                $scope.items[0] = items.content;
            }
            else {
                $scope.items = items.content;
            }
            $scope.item_count = 1;
            $scope.$digest();
        }
    };
    clickFirstItem = function () {
        if($scope.item_count!==0){
            {
                if ($scope.items[0]) {
                    let noResults = $scope.items[0].indexOf("Не найдены варианты");
                    if (($scope.items[0].indexOf("Не найдены варианты") === -1) && ($scope.items[0].indexOf("Ошибка сервера") === -1))
                        $("#p_Result").find('li  span  a')[0].click();
                }
            }
        }
    };

    setItemsCount = function (data) {
        $scope.item_count = data;
        $scope.$digest();
    }
}]);
angular.module('myApp').controller('ovbaddInstance', function ($scope, $uibModalInstance, obj,
                                                               type, objects, FileUploader) {
    var $ctrl = this;
    $ctrl.objects = objects;
    $ctrl.type = type;
    $ctrl.picShow = false;
    $ctrl.picSource;
    $ctrl.groupName = "";
    $ctrl.isOk = false;
    $ctrl.id = false;
    $scope.type = "MobileDivisions";
    if (obj !== null) {
        $ctrl.id = obj._id.$oid;
        $ctrl.groupName = obj.properties.name;
        if (obj.properties.img) {
            $ctrl.picSource = obj.properties.img;
            fetch($ctrl.picSource).then(function (response) {
                return response.blob()
            })
                .then(function (blob) {
                    reader.readAsDataURL(blob);
                    $ctrl.picShow = true;
                    $ctrl.isOk = true;
                    $scope.$digest();
                });
        }
    }


    $ctrl.checkInput = function () {
        if ($ctrl.groupName === "" || $ctrl.groupName === undefined)
            $ctrl.isOk = false;
        else $ctrl.isOk = true;
    };

    $ctrl.focusCapture = function () {
        $ctrl.checkInput();
    };

    let reader = new FileReader();
    reader.onload = function (e) {
        $scope.showPic(e.target.result);
    };


    $scope.showPic = function (data) {
        $ctrl.picSource = data;
        $ctrl.picShow = true;
        $scope.$digest();
    };

    let uploader = $scope.uploader = new FileUploader({
        url: 'api/createMobileDivision'
    });

    uploader.onAfterAddingAll = function () {
        if (uploader.getNotUploadedItems().length > 1) {
            uploader.removeFromQueue(0);
        }
    };
    uploader.isFile = function isFile(value) {
        if (outsideFilter(value.name))
            reader.readAsDataURL(value);
        return this.constructor.isFile(value);
    };

    uploader.onSuccessItem = function (fileItem, response, status, headers) {
        uploader.clearQueue();
    };

    $scope.upload = function (item) {
        uploader.queue[uploader.queue.indexOf(item)].upload();
    };

    $scope.cancel = function (item) {
        uploader.queue[uploader.queue.indexOf(item)].cancel();
    };

    //

    let outsideFilter = function (name) {
        let pos = name.lastIndexOf('.');
        let type = name.slice(pos + 1, pos + 4);
        if (type === "svg") return true;
        else return false;
    };

    uploader.filters.push({
        name: 'svgFilter',
        fn: function (item, options) {
            let pos = item.name.lastIndexOf('.');
            let type = item.name.slice(pos + 1, pos + 4);
            if (type === "svg") return 1;
        }
    });


    uploader.onBeforeUploadItem = function (item) {
        item.formData.push({name: $ctrl.groupName});
        if ($ctrl.id){
            item.formData.push({id: $ctrl.id});
        }
    };

    $ctrl.ok = function () {
        if (uploader.queue[0])
            uploader.uploadAll();
        else {
            let url = "api/createMobileDivision?name="+$ctrl.groupName;
            if ($ctrl.id)
                url+="&id="+$ctrl.id;
            $ctrl.objects.simpleRequest(url);
        }
        $uibModalInstance.close();
    };

    $ctrl.cancel = function () {
        $uibModalInstance.close();
    };


    $ctrl.checkInput();
});
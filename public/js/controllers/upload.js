myApp.controller('UploadController', function ($scope, FileUploader) {
    $scope.buttons = false;
    $scope.counter=0;
    $scope.log = [];
    var uploader = $scope.uploader = new FileUploader({
        url: 'api/upload'
    });

    uploader.onSuccessItem = function (fileItem, response, status, headers) {
        $scope.log.push([fileItem.file.name, response.answer]);
        $scope.counter--;
    };

    $scope.upload=function (item) {
        $scope.counter++;
        uploader.queue[uploader.queue.indexOf(item)].upload();
    };
    $scope.uploadAll=function () {
        $scope.counter+=uploader.queue.length;
        uploader.uploadAll();
    };
    $scope.cancelAll=function(){
        for(let i =0 ; i<uploader.queue.length;i++){
            if(uploader.queue[i].isUploading ){
                uploader.queue[i].cancel();
                $scope.counter--;
            }
        }
    };
    $scope.cancel=function (item) {
        $scope.counter--;
        uploader.queue[uploader.queue.indexOf(item)].cancel();
    };

    let uploadCounter = function(){
        for (let i=0;i<uploader.queue.length;i++)
            if (uploader.queue[i].isUploading===true){
                return false;
            }
        return true;
    };

    $scope.spinWatch=function () {
        let isReady = uploadCounter();
        if(isReady===true){
            document.getElementById('loader').classList.add('ng-hide');
        }
        else{
            document.getElementById('loader').classList.remove('ng-hide');
        }
    };

    $scope.queryWatch=function(){
        if ($scope.uploader.queue.length===0)
            $scope.buttons=false;
        else
            $scope.buttons=true;
    };

    $scope.$watch('counter', $scope.spinWatch);
    $scope.$watch('uploader.queue.length',$scope.queryWatch);

    // FILTERS

    uploader.filters.push({
        name: 'customFilter',
        fn: function (item /*{File|FileLikeObject}*/, options) {
            return this.queue.length < 10;
        }
    });

    uploader.filters.push({
        name: 'xmlFilter',
        fn: function (item /*{File|FileLikeObject}*/, options) {
            var type = '|' + item.type.slice(item.type.lastIndexOf('/') + 1) + '|';
            return '|xml|'.indexOf(type) !== -1;
        }
    });

});

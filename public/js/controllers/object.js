window.myApp.controller('SearchCtrl', function ($scope, $uibModal, objects, FileUploader) {
    $scope.data = [];
    $scope.disabled = false;
    $scope.type = "";
    $scope.count = 0;
    $scope.scaleLoaded = false;
    $scope.itemPerPage = 10;
    $scope.pagination = {
        current: 1
    };
    $scope.objects = objects;

    Array.prototype.uniqueId = function() {
        var a = this.concat();
        for(var i=0; i<a.length; ++i) {
            for(var j=i+1; j<a.length; ++j) {
                if(a[i]._id.$oid === a[j]._id.$oid)
                    a.splice(j--, 1);
            }
        }

        return a;
    };

    $scope.searchWorkers = function(query){
        let urls = ['info','name','id'];
        $scope.data.items = [];
        $scope.count = 0;
        objects.severalRequests('Workers',query,undefined,urls,function(data){
            $scope.data.items = $scope.data.items.concat(data.data.features).uniqueId();
            $scope.count += data.data.features.length;
            $scope.disabled = false;
        },[]);



    };

    $scope.search = function (type, query, page) {
        $scope.disabled = true;
        $scope.type = type;
       if (type==="Workers"){
            $scope.searchWorkers(query);
            return;
        }
        var fild = "properties.tplnr";
        var parametrs = "";
        switch (type) {
            case "Ztp":
                fild = "properties.doknr";
                break;
            case "Opory":
                parametrs = "&limit=10000";
                break;
            case "Lines":
                parametrs = "&no_opory=1&limit=10000";
                break;
           case "Workers":
                fild = "properties.id";
                break;
            case "univers_objs":
                fild = "properties.name";
                break;
            case "Res":
                fild = "properties.Label";
                $scope.loadScale();
                break;
            case "UniverseWays":
                fild = "properties.name";
                break;
        }
        objects.search(type, query, 0, fild, function (data) {
            $scope.data.items = data.data.features;
            $scope.count = data.data.features.length;
            $scope.disabled = false;
            if (type === "Workers") {
                for (var i = 0; i < $scope.count; i++) {
                    var msg = $scope.data.items[i].properties.messages;
                    if (msg !== undefined) {
                        $scope.data.items[i].properties.status = msg[msg.length - 1].status;
                    }
                }
            }
            //$scope.pagination.current = (page - 1)*$scope.itemPerPage;
        }, parametrs, function(){
            $scope.disabled = false;
        });
    };

    $scope.remove = function (type, data, dell_opory) {
        if (dell_opory === undefined) {
            dell_opory = false;
        }
        objects.remove(type, data, dell_opory, function (data) {
            $scope.search($scope.type, $scope.query, $scope.pagination.current);
        });
    };
    $scope.remove_obj = function (type, data, query_field) {
        objects.remove_obj(type, data, query_field, function (data) {
            $scope.search($scope.type, $scope.query, $scope.pagination.current);
        });
    };

    $scope.deleteDivision=function(id){
        objects.simpleRequest("api/deleteMobileDivision?id="+id,function(){

        })
    };

    $scope.delete_obj = function (type, field, typefield) {
        objects.delete_obj(type, field, typefield, function (data) {
            $scope.search($scope.type, $scope.query, $scope.pagination.current);
        });
    };

    $scope.update_obj = function (id, idfield, type, typefield, field) {
        objects.update(id, idfield, type, typefield, field, function (data) {
            $scope.search($scope.type, $scope.query, $scope.pagination.current);
        })
    };


    $scope.open = function (size, obj, type_modal) {
        var modalInstance = $uibModal.open({
            animation: $scope.animationsEnabled,
            ariaLabelledBy: 'modal-title',
            ariaDescribedBy: 'modal-body',
            templateUrl: type_modal + 'ModalContent',
            controller: type_modal + 'Instance',
            controllerAs: '$ctrl',
            size: size,
            resolve: {
                obj: function () {
                    return obj;
                },
                type: function () {
                    return $scope.type;
                }
            },
            objects: objects,
            FileUploader: FileUploader
        });
        modalInstance.closed.then(function () {
            $scope.search($scope.type, $scope.query, $scope.pagination);
        }, function () {
            console.error("Modal close promise was rejected");
        });


        modalInstance.result.then(function () {

        }, function () {

        });
    };
    $scope.pageChanged = function (newPage) {
        $scope.pagination.current = newPage;
    };

    $scope.send_message = function (message, callback) {
        objects.send_message(message, callback);
    };
    $scope.loadScale = function () {
        objects.search('ScaleStat', undefined, 0, 'properties', function (data) {
            $scope.filiationScale = Number(data.data.features[0].properties.filiationScale);
            $scope.poScale = Number(data.data.features[0].properties.poScale);
            $scope.resScale = Number(data.data.features[0].properties.resScale);
            $scope.scaleId = data.data.features[0]._id['$oid'];
            $scope.scaleLoaded = true;
        }, '');
    }
    $scope.saveScale = function () {
        objects.update($scope.scaleId, '_id', 'ScaleStat', 'resScale', $scope.resScale);
        objects.update($scope.scaleId, '_id', 'ScaleStat', 'poScale', $scope.poScale);
        objects.update($scope.scaleId, '_id', 'ScaleStat', 'filiationScale', $scope.filiationScale);
    }
});
//this one
angular.module('myApp').controller('electricInstance', function ($scope, $uibModalInstance, obj,
                                                                 type, objects, FileUploader) {
    var $ctrl = this;
    $ctrl.items = obj.properties.hrefs;
    $ctrl.objects = objects;
    $ctrl.info = obj.properties.info;
    $ctrl.name = obj.properties.d_name || obj.properties.NoInLine || obj.properties.name;
    $ctrl.type = type;
    $ctrl.highlight = obj.properties.highlight ? obj.properties.highlight : false;
    $ctrl.buildPercentShow = obj.properties.buildingPercent !== undefined;
    $ctrl.buildPercent = obj.properties.buildingPercent ? obj.properties.buildingPercent : 0;
    if (obj.geometry) $ctrl.geometry = obj.geometry;
    $ctrl.IsCorrect = false;
    $ctrl.workersType=[];

    if (obj.properties.email)
        $ctrl.email = obj.properties.email;

    $scope.$watch('$ctrl.workerType', function(newVal, oldVal){
        objects.update($ctrl.id, $ctrl.idfield, $ctrl.type, 'type', $ctrl.workerType);
    });

    $ctrl.tool = false;
    if (obj.properties.tooltip)
        if (obj.properties.tooltip.hrefs) {
            $ctrl.tooltip = obj.properties.tooltip;
            $ctrl.tool = true;
        }
    if (type === 'Workers') {
        $ctrl.id = obj.properties.id;
        $ctrl.idfield = "id";
        $ctrl.workerType = obj.properties.type;
        let und = {
            _id: {
                $oid: 0
            },
            properties:{
                name: "Неопределенные"
            }
        };
        objects.simpleRequest("api/getobjs?type=MobileDivisions",function(data){
            let arr = data.data.features;
            $ctrl.workersType=[];
            $ctrl.workersType.push(und);
            for (let i=0;i<arr.length;i++){
                $ctrl.workersType.push(arr[i]);
            }
        });
    } else {
        $ctrl.id = obj.properties.tplnr;
        $ctrl.idfield = "tplnr";
    }
    if (obj.properties.hrefs === undefined) {
        $ctrl.items = [];
    }

    $scope.uploader = new FileUploader({
        url: 'api/upload'
    });

    $ctrl.IsGoodInput = function (str, ngclass) {
        if ((str) && (str != null)) {
            $ctrl.IsCorrect = true;
            return ngclass + " has-succes";
        }
        else {
            $ctrl.IsCorrect = false;
            return ngclass + " has-error";
        }
    }

    $ctrl.DelHref = function (num) {
        $ctrl.tooltip.hrefs.splice(num, num + 1);
    }

    $ctrl.ok = function () {
        $uibModalInstance.close();
        if (!$ctrl.info) $ctrl.info="";
        if (!$ctrl.name) $ctrl.name="";
        if (!$ctrl.email) $ctrl.email="";
        obj.properties.hrefs = $ctrl.items;
        obj.properties.info = $ctrl.info;
        obj.properties.name = $ctrl.name;
        obj.properties.email = $ctrl.email;
        $ctrl.objects.update($ctrl.id, $ctrl.idfield, $ctrl.type, 'name', $ctrl.name);
        $ctrl.objects.update($ctrl.id, $ctrl.idfield, $ctrl.type, 'email', $ctrl.email);
        $ctrl.objects.update($ctrl.id, $ctrl.idfield, $ctrl.type, 'info', $ctrl.info);

        if ($ctrl.tool === true) objects.update($ctrl.id, $ctrl.idfield, $ctrl.type, "tooltip", $ctrl.tooltip, function (data) {
        });
        else objects.update($ctrl.id, $ctrl.idfield, $ctrl.type, "tooltip", {}, function (data) {
        });
    };

    $ctrl.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
    $ctrl.add_href = function (e) {
        var item = [];
        var obj = e.data;
        item.href = obj.href;
        item.name = obj.name;
        item.type_href = obj.type_href;
        item.id = obj.id;
        $ctrl.items.push(item);
        $scope.$scope.href = "";
        $scope.$scope.name = "";
        $scope.$scope.type_href = "";
    };

    $ctrl.delete_href = function (e) {
        var index = -1;
        for (var i = 0; i < $ctrl.items.length; i++) {
            if ($ctrl.items[i].id === e.data.id) {
                index = i;
                break;
            }
        }
        if (index > -1) {
            $ctrl.items.splice(index, 1);
        }
    };

    $ctrl.AddToolRef = function (name, link) {
        var newref = {};
        newref.name = name;
        newref.href = link;
        $ctrl.linkname = undefined;
        $ctrl.link = undefined;
        $ctrl.newname = "";
        $ctrl.newlink = "";
        if ($ctrl.tooltip)
            $ctrl.tooltip.hrefs.push(newref);
        else {
            $ctrl.tooltip = {};
            $ctrl.tooltip.hrefs = [];
            $ctrl.tooltip.description = "";
            $ctrl.tooltip.hrefs.push(newref);
        }
    }
});



angular.module('myApp').controller('universInstance', function ($scope, $uibModalInstance, obj,
                                                                type, objects, FileUploader) {
    var $ctrl = this;
    $ctrl.items = obj.properties.hrefs;
    $ctrl.objects = objects;
    $ctrl.name = obj.properties.d_name;
    $ctrl.id = obj._id;
    $ctrl.buildPercentShow = false;
    $ctrl.buildPercent = 0;
    $ctrl.info = obj.properties.info;
    $scope.info = obj.properties.info;
    if (obj.properties.hrefs === undefined) {
        $ctrl.items = [];
    }
    $ctrl.type = type;

    $scope.uploader = new FileUploader({
        url: 'api/uploadtmp'
    });
    $ctrl.ok = function () {
        $uibModalInstance.close();
    };

    $ctrl.cancel = function () {
        $uibModalInstance.dismiss('cancel');
    };
    $ctrl.add_href = function (e) {
        var item = [];
        var obj = e.data;
        item.href = obj.href;
        item.name = obj.name;
        item.type_href = obj.type_href;
        item.id = obj.id;
        $ctrl.items.push(item);
        $scope.$scope.href = "";
        $scope.$scope.name = "";
        $scope.$scope.type_href = "";
    };

    $ctrl.delete_href = function (e) {
        var index = -1;
        for (var i = 0; i < $ctrl.items.length; i++) {
            if ($ctrl.items[i].id === e.data.id) {
                index = i;
                break;
            }
        }
        if (index > -1) {
            $ctrl.items.splice(index, 1);
        }
    };
});


angular.module('myApp').controller('UnObAddInstance', function ($scope, $uibModalInstance, obj,
                                                                type, objects, FileUploader) {

    var MakeGreen = function (style, type) {
        style = type + " has-success";
        return style;
    };

    var MakeRed = function (style, type) {
        style = type + " has-error";
        return style;
    };

    var Isll = function (ll) {
        if (isNaN(ll) || (ll === '') || (!ll))
            return false;
        else if ((ll > 180) || (ll < 0))
            return false;
        return true;
    };

    var $ctrl = this;
    $scope.send = false;
    $scope.display = true;
    $scope.info = '';

    $scope.namest = "form-group";
    $scope.llst = "form-inline";
    $scope.groupst = "form-group";

    $ctrl.objects = objects;
    $ctrl.type = type;


    $scope.check = function () {
        $scope.send = true;
        $scope.llst = MakeGreen($scope.llst, "form-inline");
        $scope.namest = MakeGreen($scope.namest, "form-group");
        $scope.groupst = MakeGreen($scope.groupst, "form-group");
        if (($scope.name === '') || (!$scope.name)) {
            $scope.send = false;
            $scope.namest = MakeRed($scope.namest, "form-group");
        }
        if ((!Isll($scope.lon)) || (!Isll($scope.lat))) {
            $scope.send = false;
            $scope.llst = MakeRed($scope.llst, "form-inline");
        }
        if (($scope.group === '') || (!$scope.group)) {
            $scope.send = false;
            $scope.groupst = MakeRed($scope.groupst, "form-group");
        }
        $scope.$digest;
    };

    $scope.cancel = function () {
        $uibModalInstance.close();
    };

    $scope.add = function () {
        objects.add($scope.name, $scope.lat, $scope.lon, $scope.group, $scope.display, $scope.info, function () {
        });
        $uibModalInstance.close();
    };

});

myApp.controller('MessageCtrl', function ($scope, $uibModalInstance, $rootScope, data, admin, objects) {
    var MaxW = 770;
    var MaxH = 450;

    var $ctrl = this;
    $ctrl.MaxW = MaxW;
    $ctrl.MaxH = MaxH;
    $ctrl.id = data._id.$oid;
    $ctrl.data = data.properties;
    if (!$ctrl.data.vis)
        $ctrl.data.vis = false;
    $ctrl.admin = admin;

    var RemoveMarker = function () {
        CurrentMarker.closeTooltip();
        CurrentMarker.remove();
    }

    $ctrl.update = function (coll, type, field) {
        objects.update($ctrl.id, "_id", coll, type, field, function (data) {
            if (CurrentMarker)
                if ($ctrl.data.vis == true) {
                    RemoveMarker();
                }
        });
    };

    $ctrl.ok = function (coll, type, field) {
        if (CurrentMarker)
            if ($ctrl.data.vis == true) {
                $uibModalInstance.close();
            }
        objects.update($ctrl.id, "_id", coll, type, field, function (data) {
        });
        $uibModalInstance.close();
    };


    $ctrl.ZoomItem = null;
    $ctrl.ZoomMedia = false;
    $ctrl.img = null;
    $ctrl.IsPic = false;
    $ctrl.IsVideo = false;
    $ctrl.IsAudio = false;

    $ctrl.Zoom = function (obj) {
        if (obj.type == "image") {
            $ctrl.ZoomPic = obj;
            var img = new Image();
            img.src = obj.href;
            img.onload = function () {
                var res = ResizePicture(img.width, img.height, MaxW, MaxH);
                var width = res[0];
                var height = res[1];
                res = GetPictureCenter(width, height, MaxW, MaxH);
                var PadL = res[0];
                var PadT = res[1];
                $ctrl.PicStyleBorder = {
                    'padding-left': PadL + 'px',
                    'padding-top': PadT + 'px',
                    'width': MaxW + 'px',
                    'height': MaxH + 'px'
                };
                $ctrl.PicStyle = {
                    'width': width + 'px',
                    'height': height + 'px'
                };
                $rootScope.$digest();
            };
        }


        if (obj.type == "video") {
            var vid = document.getElementsByTagName("video");
            if (vid[0]) {
                vid[0].pause();
                vid[0].src = obj.href;
            }
            $ctrl.ZoomVideo = obj;
            $ctrl.VideoStyle = {
                'width': MaxW + 'px',
                'height': MaxH + 'px'
            };
        }


        if (obj.type == "audio") {
            var aud = document.getElementsByTagName("audio");
            if (aud[0]) {
                aud[0].pause();
                aud[0].src = obj.href;
            }
            $ctrl.ZoomAudio = obj;
        }

    }

    $ctrl.ZoomClose = function () {
        $ctrl.ZoomMedia = false;
    };

    $ctrl.media = $ctrl.data.hrefs;

    $ctrl.Init = function () {

        $ctrl.video = [];
        $ctrl.IsVideo = false;
        $ctrl.IsVideoPlay = false;
        $ctrl.audio = [];
        $ctrl.IsAudio = false;
        $ctrl.IsAudioPlay = false;
        $ctrl.image = [];
        $ctrl.IsImage = false;
        $ctrl.document = [];
        for (var i = 0; i < $ctrl.media.length; i++) {

            if ($ctrl.media[i].type == "video") {
                $ctrl.video.push($ctrl.media[i]);
                $ctrl.IsVideo = true;
            }
            if ($ctrl.media[i].type == "audio") {
                $ctrl.audio.push($ctrl.media[i]);
                $ctrl.IsAudio = true;
            }
            if ($ctrl.media[i].type == "image") {
                $ctrl.image.push($ctrl.media[i]);
                $ctrl.IsImage = true;
            }
            if (($ctrl.media[i].type == "document") || (!$ctrl.media[i].type) || ($ctrl.media[i].type == "undefiend")) $ctrl.document.push($ctrl.media[i]);
        }

        if ($ctrl.IsVideo == true) $ctrl.IsVideoPlay = true;
        else if ($ctrl.IsAudio == true) $ctrl.IsAudioPlay = true;

        if ($ctrl.image[0]) $ctrl.Zoom($ctrl.image[0]);
        if ($ctrl.video[0]) $ctrl.Zoom($ctrl.video[0]);
        if ($ctrl.audio[0]) $ctrl.Zoom($ctrl.audio[0]);
    };

    if ($ctrl.media)
        $ctrl.Init();
    $scope.workInfo = $ctrl.data.info;
});


angular.module('myApp').controller('resInstance', function ($scope, $uibModalInstance, obj,
                                                            type, objects, FileUploader) {
    var $ctrl = this;
    $ctrl.objects = objects;
    $ctrl.type = type;
    $ctrl.id = obj._id['$oid'];
    $ctrl.color = obj.properties.color.toLowerCase();

    $ctrl.ok = function () {
        saveData();
        $uibModalInstance.close();
    }

    saveData = function () {
        $ctrl.objects.update($ctrl.id, '_id', $ctrl.type, 'color', $ctrl.color);
    }
});
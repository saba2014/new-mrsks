function Container(map, Leaflet) {
    this.map = map;
    this.L = Leaflet;
    this.objects = [];
    this.line_kl = [];
    this.line_vl = [];
    this.tap = [];
    this.opory_kl = [];
    this.opory_vl = [];
    this.tp = [];
    this.tp_area = [];
    this.ps = [];
    this.ps_area = [];
    this.rp = [];
    this.rp_area = [];
    this.ztp = [];
    this.workers_mobile = [];
    this.workers_counters = [];
    this.workers_tracks = [];
    this.univers_ways = [];
    this.univers_ways_objects = [];
    this.univers_line = [];
    this.univers_line_kl = [];
    this.univers_line_vl = [];
    this.univers_tp = [];
    this.univers_ps = [];
    this.univers_rp = [];
    this.univers_opory_kl = [];
    this.univers_opory_vl = [];
    this.check_events = [];
    this.not_check_events = [];
    this.res_centers = [];
    this.zoomPS = 11;
    this.zoomZTP = 11;
    this.zoomLoss = 10;
    this.zoomLN_max = 12;
    this.zoomLN_middle = 11;
    this.zoomLN_min = 7;
    this.zoomOpory = 13;
    this.overlay_layer_tree = new n_ary_tree(null, null);
    this.layer_tree = new n_ary_tree(null, null);
    this.near = 50;
    this.cluster = new Cluster();
    this.colors = ['#ff0000', '#00ff00', '#fff400', '#000000', '#0000FF'];
    this.cluster_segment = new Cluster_segment();
    this.menu = new Leaflet.Control.Menu_tree();
    this.admin = 0;
    this.loop = 0;
    this.loopLayers = [];
    this.mobileDivisions = [];
};

Container.prototype.Set_Menu = function (menu) {
    for (var i = 0; i < menu.length; i++) {
        var tree = new n_ary_tree(null, null);
        this._set_menu(menu[i].child, tree);
        this.menu.Add_tree(tree);
    }
};

Container.prototype.Set_Masks = function (masks) {
    this.masks = masks;
    var i, obj, obj_area, n = masks.line.length;
    for (i = 0; i < n; i++) {
        obj = masks.line[i];
        var line = new Line(this.map, this.L, obj.voltage, obj.color, obj.type_line);
        line.Set_Zoom(this.zoomLN_max);
        if (obj.voltage >= 10) {
            line.Set_Zoom(this.zoomLN_middle);
        }
        if (obj.voltage > 20) {
            line.Set_Zoom(this.zoomLN_min);
        }
        this.objects.push(line);
        var opory = new Opory(this.map, this.L, obj.voltage);
        opory.Set_Zoom(this.zoomOpory);
        this.objects.push(opory);

        if (obj.type_line === "КЛ") {
            this.line_kl.push(line);
            this.opory_kl.push(opory);
        }
        if (obj.type_line === "ВЛ") {
            this.line_vl.push(line);
            this.opory_vl.push(opory);
        }
    }
    n = masks.ps.length;
    for (i = 0; i < n; i++) {
        obj = masks.ps[i];
        obj = new PS(this.map, this.L, obj.voltage, obj.color, "Point");
        obj.Set_Zoom(this.zoomPS);
        this.objects.push(obj);
        this.ps.push(obj);

        obj_area = masks.ps[i];
        obj_area = new PS(this.map, this.L, obj_area.voltage, obj_area.color, "Polygon");
        this.objects.push(obj_area);
        this.ps_area.push(obj_area);

    }
    n = masks.tp.length;
    for (i = 0; i < n; i++) {
        obj = masks.tp[i];
        obj = new TP(this.map, this.L, obj.voltage, obj.color, "Point");
        obj.Set_Zoom(this.zoomPS);
        this.objects.push(obj);
        this.tp.push(obj);

        obj_area = masks.tp[i];
        obj_area = new TP(this.map, this.L, obj_area.voltage, obj_area.color, "Polygon");
        this.objects.push(obj_area);
        this.tp_area.push(obj_area);
    }
    n = masks.rp.length;
    for (i = 0; i < n; i++) {
        obj = masks.rp[i];
        obj = new RP(this.map, this.L, obj.voltage, obj.color, "Point");
        obj.Set_Zoom(this.zoomPS);
        this.objects.push(obj);
        this.rp.push(obj);

        obj_area = masks.rp[i];
        obj_area = new RP(this.map, this.L, obj_area.voltage, obj_area.color, "Polygon");
        this.objects.push(obj_area);
        this.rp_area.push(obj_area);
    }
};

Container.prototype.Univers_Set_Masks = function (masks) {
    this.univers_masks = masks;
    var i, obj, n = masks.line.length;
    for (i = 0; i < n; i++) {
        obj = masks.line[i];
        var line = new univers_Line(this.map, this.L, obj.voltage, obj.color, obj.type_line);
        line.Set_Zoom(this.zoomLN_max);
        if (obj.voltage >= 10) {
            line.Set_Zoom(this.zoomLN_middle);
        }
        if (obj.voltage > 20) {
            line.Set_Zoom(this.zoomLN_min);
        }
        this.objects.push(line);
        var opory = new Opory(this.map, this.L, obj.voltage);
        opory.Set_Zoom(this.zoomOpory);
        this.objects.push(opory);
        if (obj.type_line === "ВЛ") {
            this.univers_line_kl.push(line);
            this.univers_opory_kl.push(opory);
        }
        if (obj.type_line === "КЛ") {
            this.univers_line_vl.push(line);
            this.univers_opory_vl.push(opory);
        }
    }
    n = masks.ps.length;
    for (i = 0; i < n; i++) {
        obj = masks.ps[i];
        obj = new univers_ps(this.map, this.L, obj.voltage, obj.color);
        obj.Set_Zoom(this.zoomPS);
        this.objects.push(obj);
        this.univers_ps.push(obj);
    }
    n = masks.tp.length;
    for (i = 0; i < n; i++) {
        obj = masks.tp[i];
        obj = new univers_tp(this.map, this.L, obj.voltage, obj.color);
        obj.Set_Zoom(this.zoomPS);
        this.objects.push(obj);
        this.univers_tp.push(obj);
    }
    n = masks.rp.length;
    for (i = 0; i < n; i++) {
        obj = masks.rp[i];
        obj = new univers_rp(this.map, this.L, obj.voltage, obj.color);
        obj.Set_Zoom(this.zoomPS);
        this.objects.push(obj);
        this.univers_rp.push(obj);
    }
};

Container.prototype.setMobileDivisions = function(){
    let self = this;
    for (let i=0;i<results.mobileDivisions.length;i++){
        let div = results.mobileDivisions[i];
        let workerImg = "img/icons/worker.svg";
        let name = div.properties.name;
        if (div.properties.img)
            workerImg = div.properties.img;
        let mobile_cluster = this.cluster.get_cluster("clusters_mobile_"+name, "numbers_univers");
        let mobDiv = new Worker(self.map, self.L,div._id.$oid, undefined,workerImg);
        self.mobileDivisions.push(mobDiv);
        self.objects.push(self.mobileDivisions[i]);
    }
};

Container.prototype.Set_Layers = function (url, shift) {
    //заявители без затрат
    this.ztp[0] = new ZTP(this.map, this.L, 0, 0, 0);
    this.ztp[0].Set_Zoom(this.zoomZTP);
    this.objects.push(this.ztp[0]);
    this.ztp[1] = new ZTP(this.map, this.L, 1, 1, 0);
    this.ztp[1].Set_Zoom(this.zoomZTP);
    this.objects.push(this.ztp[1]);
    this.ztp[2] = new ZTP(this.map, this.L, 2, 1000, 0);
    this.ztp[2].Set_Zoom(this.zoomZTP);
    this.objects.push(this.ztp[2]);
    //заявители с затратами
    this.ztp[3] = new ZTP(this.map, this.L, 0, 0, 1);
    this.ztp[3].Set_Zoom(this.zoomZTP);
    this.objects.push(this.ztp[3]);
    this.ztp[4] = new ZTP(this.map, this.L, 1, 1, 1);
    this.ztp[4].Set_Zoom(this.zoomZTP);
    this.objects.push(this.ztp[4]);
    this.ztp[5] = new ZTP(this.map, this.L, 2, 1000, 1);
    this.ztp[5].Set_Zoom(this.zoomZTP);
    this.objects.push(this.ztp[5]);

    this.psLoss = new psLoss(this.map, this.L);
    this.psLoss.Cluster = this.cluster_segment.get_cluster(this.L, this.map, this.colors);
    this.psLoss.Set_Zoom(this.zoomLoss);
    this.objects.push(this.psLoss);
    this.worker = new Worker(this.map, this.L, "walk",undefined, "img/icons/worker.svg");
    //this.worker.track.Cluster = this.cluster.get_cluster("clusters_univers", "numbers_univers");
    this.objects.push(this.worker);
    this.worker_kras = new Worker(this.map, this.L, "walk", 2400);
    //this.worker_kras.track.Cluster = this.cluster.get_cluster("clusters_univers", "numbers_univers");
    this.objects.push(this.worker_kras);
    this.worker_car_kras = new Worker(this.map, this.L, "car", 2400);
    //this.worker_car_kras.track.Cluster = this.cluster.get_cluster("clusters_univers", "numbers_univers");
    this.objects.push(this.worker_car_kras);
    this.worker_car = new Worker(this.map, this.L, "car", undefined, "img/icons/car.svg");
    //this.worker_car.track.Cluster = this.cluster.get_cluster("clusters_univers", "numbers_univers");
    this.setMobileDivisions();
    this.objects.push(this.worker_car);
    this.kadastr = new Kadastr(this.map, this.L, url, shift);
    this.objects.push(this.kadastr);
    this.special_zone = new Special_zone(this.map, this.L, url);
    this.objects.push(this.special_zone);
    this.critical_objects = new CriticalObjects(this.map, this.L);
    this.objects.push(this.critical_objects);
    //  this.universeWays = new UniverseWays(this.map, this.L);
    //   this.objects.push(this.universeWays);
};

Container.prototype.Set_Territory = function (terr) {
    let img = document.createElement('div');
    img.style.marginRight = '5px';
    img.style.marginTop = '-1px';
    img.style.width = "20px";
    img.style.height = "20px";
    img.innerHTML = "<img src='img/icons/territory.svg' height='20px' width='20px'>";
    var terr_tree = this.layer_tree.Add(this._get_key_tree("Территория обслуживания", 0, img, 20));
    var self = this;
    self.ress = [];
    let resCluster = self.cluster_segment.getResCluster();
    $.each(terr, function (i, item) {
        var po_tree = terr_tree.Add(self._get_key_tree(i, 0, undefined, undefined, undefined, item.self));
        let filiation = new Filiation(self.map, self.L, item.self.id);
        $.each(item.po, function (i, item) {
            var res_tree = po_tree.Add(self._get_key_tree(i, 0, undefined, undefined, undefined, item.self));
            let po_center = new ResCenter(self.map, self.L, item.self.composite_id, "po");
            po_center.Cluster = resCluster;
            self.objects.push(po_center);
            res_tree.Add(self._get_key_sheet("Центр ПО", po_center));

            let po_emergency = new EmergencyReserve(self.map, self.L, item.self.composite_id, "po");
            po_emergency.Cluster = resCluster;
            self.objects.push(po_emergency);
            res_tree.Add(self._get_key_sheet("Аварийный резерв", po_emergency));

            let po_rise = new Rise(self.map, self.L, item.self.composite_id, "po");
            po_rise.Cluster = resCluster;
            self.objects.push(po_rise);
            res_tree.Add(self._get_key_sheet("РИСЭ", po_rise));
            let po = new Po(self.map, self.L, item.self.composite_id, filiation);
            $.each(item.res, function (i, item) {
                var res = res_tree.Add(self._get_key_tree(i, 0, undefined, undefined, undefined, item));
                let res_center = new ResCenter(self.map, self.L, item.RES_id, "res");
                res_center.Cluster = resCluster;
                let res_layer = new Res(self.map, self.L, item.RES_id, po);
                let emergencyLayer = new EmergencyReserve(self.map, self.L, item.RES_id, "res");
                emergencyLayer.Cluster = resCluster;
                let rise = new Rise(self.map, self.L, item.RES_id, "res");
                rise.Cluster = resCluster;
                self.objects.push(res_layer);
                self.ress.push(res_layer);
                res.Add(self._get_key_sheet("РЭС", res_layer));
                self.objects.push(res_center);
                self.res_centers.push(res_center);
                res.Add(self._get_key_sheet("Центр РЭС", res_center, undefined, undefined, 0));

                self.objects.push(emergencyLayer);
                res.Add(self._get_key_sheet("Аварийный резерв", emergencyLayer, undefined, undefined, 0));

                self.objects.push(rise);
                res.Add(self._get_key_sheet("РИСЭ", rise, undefined, undefined, 0));
            });
        });
    });
    if (this.admin) {
        this._set_territory_univers(terr);
    }
};

Container.prototype._set_univers = function () {
    this.univers_regions = new Administrative_regions(this.map, this.L);
    this.objects.push(this.univers_regions);
};

Container.prototype._set_territory_univers = function (terr) {
    var terr_tree = this.universiade.Add(this._get_key_tree("Территория обслуживания универсиады", 0));
    var self = this;
    var res = terr["Красноярскэнерго"].po["ПО Красноярские ЭС"].res;
    $.each(res, function (i, item) {
        if ((i !== "Емельяновский РЭС") && (i !== "Березовский РЭС")) {
            var res_layer = new Res(self.map, self.L, item.RES_id);
            terr_tree.Add(self._get_key_sheet(i, res_layer));
            self.objects.push(res_layer);
            self.ress.push(res_layer);
        }
    });
};

Container.prototype._set_univers_ways = function (allWays) {
    for (let i = 0; i < allWays.length; i++) {
        let obj = new UniverseWay(this.map, this.L, allWays[i].id.$oid);
        this.univers_ways.push(obj);
        this.objects.push(obj);
        let obj2 = new UniversWayObjects(this.map, this.L, allWays[i].id.$oid);
        this.univers_ways_objects.push(obj2);
        this.objects.push(obj2);
    }
};

Container.prototype._add_univers_objs = function (type, univers_claster) {
    var sport_objs = new univers_objs(this.map, this.L, type);
    sport_objs.Cluster = univers_claster;
    this.objects.push(sport_objs);
    return sport_objs;
};

Container.prototype._set_overlayMap = function () {


    var image = new SVG_image(20, 20, "#000");
    this.overlay_layer_tree.Add(this._get_key_sheet("Карта РосРеестра (pkk5)", this.kadastr));
    this.overlay_layer_tree.Add(this._get_key_sheet("Зоны с особыми условиями использования территории", this.special_zone));
    var line_tree = this.layer_tree.Add(this._get_key_tree("ЛЭП и опоры МРСК", 0, image.Get_Image("line", "line"), image.image_width));
    var line_vl = line_tree.Add(this._get_key_tree("ВЛ", 0, image.Get_Image("line", "line"), image.image_width));
    var line_kl = line_tree.Add(this._get_key_tree("КЛ", 0, image.Get_Image("line", "line"), image.image_width));
    var substation_tree = this.layer_tree.Add(this._get_key_tree("Подстанции", 0, image.Get_Image("collage", "pods"), image.image_width));
    var PS_tree = substation_tree.Add(this._get_key_tree("ПС", 0, image.Get_Image("circle", "ps"), image.image_width));
    var TP_tree = substation_tree.Add(this._get_key_tree("ТП", 0, image.Get_Image("triangle", "tp"), image.image_width));
    var RP_tree = substation_tree.Add(this._get_key_tree("РП", 0, image.Get_Image("square", "rp"), image.image_width));
    var ZTP_tree = this.layer_tree.Add(this._get_key_tree("Заявители МРСК", 0, image.Get_Image("rhombus", "ZTP"), image.image_width));

    let img_loss = document.createElement('div');
    img_loss.style.marginRight = '5px';
    img_loss.style.marginTop = '-1px';
    img_loss.style.width = "20px";
    img_loss.style.height = "20px";
    img_loss.innerHTML = "<img src='img/icons/loss.svg' height='20px' width='20px'>";
    this.layer_tree.Add(this._get_key_sheet("Потери", this.psLoss, img_loss, 20));

    var mobile_cluster = this.cluster.get_cluster("clusters_mobile", "numbers_univers");

    this.worker.Cluster = mobile_cluster;
    this.worker_car.Cluster = mobile_cluster;
    this.worker_kras.Cluster = mobile_cluster;
    this.worker_car_kras.Cluster = mobile_cluster;

    if (this.admin) {
        let img = document.createElement('div');
        img.style.marginRight = '5px';
        img.style.marginTop = '-1px';
        img.style.width = "20px";
        img.style.height = "20px";
        img.innerHTML = "<img src='img/icons/universe.svg' height='20px' width='20px'>";
        var universiade = this.universiade = this.layer_tree.Add(this._get_key_tree("Универсиада 2019", 0,
            img, 20));
        var univ_electro = universiade.Add(this._get_key_tree("Объекты электроснабжения", 0));
        var univ_substation_tree = univ_electro.Add(this._get_key_tree("Подстанции", 0, image.Get_Image("collage", "pods"), image.image_width));
        var univ_PS_tree = univ_substation_tree.Add(this._get_key_tree("ПС", 0, image.Get_Image("circle", "ps"), image.image_width));
        var univ_TP_tree = univ_substation_tree.Add(this._get_key_tree("ТП", 0, image.Get_Image("triangle", "tp"), image.image_width));
        var univ_RP_tree = univ_substation_tree.Add(this._get_key_tree("РП", 0, image.Get_Image("square", "rp"), image.image_width));
        var univ_line_tree = univ_electro.Add(this._get_key_tree("ЛЭП", 0, image.Get_Image("line", "line"), image.image_width));
        var univ_kl = univ_line_tree.Add(this._get_key_tree("КЛ", 0, image.Get_Image("line", "line"), image.image_width));
        var univ_vl = univ_line_tree.Add(this._get_key_tree("ВЛ", 0, image.Get_Image("line", "line"), image.image_width));
        //var univ = univ_line_tree.Add(this._get_key_tree("Нет данных", 0, image.Get_Image("line", "line"), image.image_width));

        var univers_claster = this.cluster.get_cluster("clusters_univers", "numbers_univers");
        var univ_obj = universiade.Add(this._get_key_tree("Объекты Универсиады", 0));
        univ_obj.Add(this._get_key_sheet("Спортивные объекты", this._add_univers_objs(1, univers_claster)));
        univ_obj.Add(this._get_key_sheet("Объекты деревни универсиады", this._add_univers_objs(2, univers_claster)));
        univ_obj.Add(this._get_key_sheet("Объекты размещения", this._add_univers_objs(3, univers_claster)));
        univ_obj.Add(this._get_key_sheet("Медицинские объекты", this._add_univers_objs(4, univers_claster)));
        univ_obj.Add(this._get_key_sheet("Объекты инфраструктуры", this._add_univers_objs(5, univers_claster)));

        var avr_cluster = this.cluster.get_cluster("clusters_avr", "numbers_univers");
        var univ_resources = universiade.Add(this._get_key_tree("Ресурсы для проведения АВР", 0));
        univ_resources.Add(this._get_key_sheet("Аварийный резерв", this._add_univers_objs(6, avr_cluster)));
        univ_resources.Add(this._get_key_sheet("Места дислокации ремонтных бригад", this._add_univers_objs(7, avr_cluster)));
        univ_resources.Add(this._get_key_sheet("Дислокация ОВБ", this._add_univers_objs(8, avr_cluster)));
        univ_resources.Add(this._get_key_sheet("Здание АУ ПАО «МРСК Сибири»", this._add_univers_objs(9, avr_cluster)));
        univ_resources.Add(this._get_key_sheet("Базы ПО/РЭС", this._add_univers_objs(10, avr_cluster)));
        univ_resources.Add(this._get_key_sheet("Места базирования мобильных РИСЭ", this._add_univers_objs(11, avr_cluster)));

        var additional_cluster = this.cluster.get_cluster("clusters_additional", "numbers_univers");
        var univ_addition_obj = universiade.Add(this._get_key_tree("Дополнительные объекты", 0));
        univ_addition_obj.Add(this._get_key_sheet("Транспортные магистрали", this._add_univers_objs(12, additional_cluster)));
        univ_addition_obj.Add(this._get_key_sheet("Гостиницы", this._add_univers_objs(13, additional_cluster)));
        univ_addition_obj.Add(this._get_key_sheet("Силовые ведомства", this._add_univers_objs(14, additional_cluster)));

        this._set_univers();
        universiade.Add(this._get_key_sheet("Административные районы города", this.univers_regions));
        for (var i = 0; i < this.univers_line_kl.length; i++) {
            var volt = this.univers_line_kl[i].voltage.toString() + " кВ";
            //univ.Add(this._get_key_sheet(volt, this.univers_line[i], this.univers_line[i].Get_Image(), this.univers_line[i].image_width));
            univ_kl.Add(this._get_key_sheet(volt, this.univers_line_kl[i], this.univers_line_kl[i].Get_Image(), this.univers_line_kl[i].image_width));
        }
        for (var i = 0; i < this.univers_line_vl.length; i++) {
            var volt = this.univers_line_vl[i].voltage.toString() + " кВ";
            univ_vl.Add(this._get_key_sheet(volt, this.univers_line_vl[i], this.univers_line_vl[i].Get_Image(), this.univers_line_vl[i].image_width));
        }
        for (var i = 0; i < this.univers_ps.length; i++) {
            var volt = this.univers_ps[i].voltage.toString() + " кВ";
            univ_PS_tree.Add(this._get_key_sheet(volt, this.univers_ps[i], this.univers_ps[i].Get_Image(), this.univers_ps[i].image_width));
        }
        for (var i = 0; i < this.univers_tp.length; i++) {
            var volt = this.univers_tp[i].voltage.toString() + " кВ";
            univ_TP_tree.Add(this._get_key_sheet(volt, this.univers_tp[i], this.univers_tp[i].Get_Image(), this.univers_tp[i].image_width));
        }
        for (var i = 0; i < this.univers_rp.length; i++) {
            var volt = this.univers_rp[i].voltage.toString() + " кВ";
            univ_RP_tree.Add(this._get_key_sheet(volt, this.univers_rp[i], this.univers_rp[i].Get_Image(), this.univers_rp[i].image_width));
        }
        var mobile_objs_tree = universiade.Add(this._get_key_tree("Мобильные объекты", 0));
        mobile_objs_tree.Add(this._get_key_sheet("Работники ОВБ", this.worker_kras, image.Get_Image("worker", "worker"),
            image.image_width));
        mobile_objs_tree.Add(this._get_key_sheet("Автотранспорт ОВБ", this.worker_car_kras, image.Get_Image("car", "car"),
            image.image_width));
        // переписать на один слой треки воркеров и счетчики
        let imgCritical = document.createElement('div');
        imgCritical.style.marginRight = '0px';
        imgCritical.style.marginTop = '-1px';
        imgCritical.style.width = "20px";
        imgCritical.style.height = "20px";
        imgCritical.innerHTML = "<img src='img/icons/CriticalObject.png' width='20px' height='20px' style='margin-left: -5px; margin-top: -5px'>";
        universiade.Add(this._get_key_sheet("Критически важные объекты", this.critical_objects, imgCritical,
            10));
        let self = this;
        let allWays = results.ways;
        this._set_univers_ways(allWays);
        let univ_ways = universiade.Add(this._get_key_tree("Маршруты движения участников и гостей Универсиады", 0));
        for (let i = 0; i < allWays.length; i++) {
            let path = univ_ways.Add(this._get_key_tree(allWays[i].obj.name, 0));
            path.Add(this._get_key_sheet("Маршут", this.univers_ways[i], undefined, 0));
            path.Add(this._get_key_sheet("Объекты маршрута", this.univers_ways_objects[i], undefined, 0));
        }
    }


    var mobile_objs_tree_root = this.layer_tree.Add(this._get_key_tree("Мобильные объекты", 0, image.Get_Image("worker", "worker")));
    var mobile_controllers = mobile_objs_tree_root.Add(this._get_key_tree("Мобильные контролёры", 0, image.Get_Image("worker", "worker"),
        image.image_width));

    let mobDiv = results.mobileDivisions;
    for (let i = 0; i < mobDiv.length; i++) {
        let name = mobDiv[i].properties.name;
        this.mobileDivisions[i].Cluster = mobile_cluster;
        let img = image.get_svg_by_url(mobDiv[i].properties.img);
        mobile_objs_tree_root.Add(this._get_key_sheet(name, this.mobileDivisions[i], img, image.image_width));
    }


    for (let i = 0; i < allRegions.length; i++) {
        let currentRegion = mobile_controllers.Add(this._get_key_tree(allRegions[i].name, 0));
        let workers_mobile = new WorkersMobile(this.map, this.L);
        this.workers_mobile.push(workers_mobile);
        this.objects.push(workers_mobile);
        let track = new WorkersTracks(this.map, this.L);
        let counter = new WorkersCounters(this.map, this.L);
        this.objects.push(track);
        this.objects.push(counter);
        this.workers_counters.push(counter);
        this.workers_tracks.push(track);
        currentRegion.Add(this._get_key_sheet("Контролеры", workers_mobile, undefined, undefined, 0, allRegions[i].id));
        currentRegion.Add(this._get_key_sheet("Счетчики", counter, undefined, undefined, 0, allRegions[i].id));
        currentRegion.Add(this._get_key_sheet("Трэки", track, undefined, undefined, 0, allRegions[i].id));
    }

    this.Set_Territory(terr);

    let img = document.createElement('div');
    img.style.marginRight = '5px';
    img.style.marginTop = '-1px';
    img.style.width = "20px";
    img.style.height = "20px";
    img.innerHTML = "<img src='img/icons/warning.svg' width='20px' height='20px'>";
    var mess = this.layer_tree.Add(this._get_key_tree("События", 0, img, 20, 0));

    this.Messages = new Messages(this.map, this.L);
    this.objects.push(this.Messages);
    mess.Add(this._get_key_sheet("Сообщения", this.Messages, undefined, undefined, 0));

    for (var i = 0; i < this.line_kl.length; i++) {
        var volt = this.line_kl[i].voltage.toString() + " кВ";
        var line = line_kl.Add(this._get_key_tree(volt, 0, this.line_kl[i].Get_Image(), this.line_kl[i].image_width));
        line.Add(this._get_key_sheet("Пролёты", this.line_kl[i]));
        line.Add(this._get_key_sheet("Опоры", this.opory_kl[i]));
        //line.Add(this._get_key_sheet("Отпайки", this.tap[i]));        
    }
    for (var i = 0; i < this.line_vl.length; i++) {
        var volt = this.line_vl[i].voltage.toString() + " кВ";
        var line = line_vl.Add(this._get_key_tree(volt, 0, this.line_vl[i].Get_Image(), this.line_vl[i].image_width));
        line.Add(this._get_key_sheet("Пролёты", this.line_vl[i]));
        line.Add(this._get_key_sheet("Опоры", this.opory_vl[i]));
        //line.Add(this._get_key_sheet("Отпайки", this.tap[i]));        
    }

    //    var PS_tree = substation_tree.Add(this._get_key_tree("ПС", 0, image.Get_Image("circle", "ps"), image.image_width));

    for (var i = 0; i < this.ps.length; i++) {
        var volt = this.ps[i].voltage.toString() + " кВ";
        var curr_ps = PS_tree.Add(this._get_key_tree(volt, 0, this.ps[i].Get_Image(), this.ps[i].image_width));
        curr_ps.Add(this._get_key_sheet("подстанция", this.ps[i], undefined, undefined));
        curr_ps.Add(this._get_key_sheet("площадь", this.ps_area[i], undefined, undefined));
    }
    for (var i = 0; i < this.tp.length; i++) {
        var volt = this.tp[i].voltage.toString() + " кВ";
        var curr_ps = TP_tree.Add(this._get_key_tree(volt, 0, this.tp[i].Get_Image(), this.tp[i].image_width));
        curr_ps.Add(this._get_key_sheet("подстанция", this.tp[i], undefined, undefined));
        curr_ps.Add(this._get_key_sheet("площадь", this.tp_area[i], undefined, undefined));
    }
    for (var i = 0; i < this.rp.length; i++) {
        var volt = this.rp[i].voltage.toString() + " кВ";
        var curr_ps = RP_tree.Add(this._get_key_tree(volt, 0, this.rp[i].Get_Image(), this.rp[i].image_width));
        curr_ps.Add(this._get_key_sheet("подстанция", this.rp[i], undefined, undefined));
        curr_ps.Add(this._get_key_sheet("площадь", this.rp_area[i], undefined, undefined));
    }

    //Добавление слоев в Заявителей: "С затратами" и "Без Затрат"
    var withLosses = ZTP_tree.Add(this._get_key_tree("Без затрат", 0, image.Get_Image("rhombus", "ZTP1"), image.image_width));
    var withoutLosses = ZTP_tree.Add(this._get_key_tree("С затратами", 0, image.Get_Image("rhombus_black", "ZTP2"), image.image_width));
    withLosses.Add(this._get_key_sheet("Заявители этого года (без Затрат)", this.ztp[0], this.ztp[0].Get_Image(),
        this.ztp[0].image_width));
    withLosses.Add(this._get_key_sheet("Заявители прошлого года (без Затрат)", this.ztp[1], this.ztp[1].Get_Image(),
        this.ztp[1].image_width));
    withLosses.Add(this._get_key_sheet("Заявители прошлых лет (без Затрат)", this.ztp[2], this.ztp[2].Get_Image(),
        this.ztp[2].image_width));

    withoutLosses.Add(this._get_key_sheet("Заявители этого года (C Затратами)", this.ztp[3], this.ztp[3].Get_Image(),
        this.ztp[3].image_width));
    -
        withoutLosses.Add(this._get_key_sheet("Заявители прошлого года (C Затратами)", this.ztp[4], this.ztp[4].Get_Image(),
            this.ztp[4].image_width));
    withoutLosses.Add(this._get_key_sheet("Заявители прошлых лет (C Затратами)", this.ztp[5], this.ztp[5].Get_Image(),
        this.ztp[5].image_width));
    this._set_events();
};

Container.prototype._set_events = function () {
    var self = this;
    var func = function () {
        //self.res.Back();
        for (var i = 0; i < self.ress.length; i++) {
            self.ress[i].Back();
        }
        if (self.admin) {
            self.univers_regions.Back();
        }
        if (self._user_event !== undefined) {
            self._user_event();
        }
    };
    for (var i = 0; i < this.objects.length; i++) {
        if ((this.objects[i] !== this.special_zone)) {
            this.objects[i].Set_user_event(func);
        }
    }
};

Container.prototype.Check = function () {
    let token = new TokenStorage();
    token.checkRelevance();
    for (var i = 0; i < this.objects.length; i++) {
        this.objects[i].Check();
    }
};

Container.prototype.check_popup = function (popup) {
    if (popup === undefined) {
        return;
    }
    // if(this.worker.is_has() && this.worker.near) {
    //     this.worker.check_popup(popup, this.near);
    // };
    // if(this.worker_car.is_has() && this.worker_car.near) {
    //     this.worker_car.check_popup(popup, this.near);
    //     return;
    // };
    for (var i = 0; i < this.objects.length; i++) {
        if (this.objects[i].is_has() && this.objects[i].near) {
            this.objects[i].check_popup(popup, this.near);
        }
    }
    if (this.admin && this.worker.is_has()) {
        this.map.addLayer(this.worker.LayerGJSON);
    }

    //this.worker._check_popup();
};

Container.prototype.open_popup = function (popup) {
    if (popup === undefined) {
        return;
    }
    for (var i = 0; i < this.objects.length; i++) {
        this.objects[i].open_popup(popup);
    }
};

Container.prototype.close_popup = function (popup) {
    if (popup === undefined) {
        return;
    }
    for (var i = 0; i < this.objects.length; i++) {
        this.objects[i].close_popup(popup);
    }
};

/*

 */
var test = function (arg) {
    var pattern = /[^\d\.]\s*/g,
        cb = arg.cb;
    let latlons = arg.query.replace(/,/g, ".");
    let latlon = latlons.replace(",", " ").trim().split(pattern);
    let content = "";
}
/*

 */

Container.prototype.Set_latlonSearch = function (search) {
    this.res.Set_latlonSearch(search);
};

Container.prototype._set_menu = function (menu, tree) {
    for (var i = 0; i < menu.length; i++) {

        var new_tree = tree.Add(this._get_key_tree(menu[i].name, 0));
        //this._set_menu(menu[i].child, new_tree);
    }
};

Container.prototype._get_key_sheet = function (name, layer, image, image_size, checked, data = 0) {
    var key = [];
    key.name = name;
    key.image = image;
    key.image_size = image_size;
    key.checked = checked;
    key.arguments = 'api/getobjs?type=' + layer._type + layer.arguments;
    key.function = function () {
        let token = new TokenStorage();
        token.checkRelevance();
        key.checked = this.checked;
        if (this.checked) {
            layer.onLayerAdd(data);
        } else {
            layer.onLayerRemove(data);
        }
    };
    return key;
};

Container.prototype._get_key_sheet_empty = function (name, image, image_size) {
    var key = [];
    key.name = name;
    key.image = image;
    key.image_size = image_size;
    key.function = function () {
        key.checked = this.checked;
    };
    return key;
};

Container.prototype._get_key_tree = function (name, exclusive, image, image_size, checked, metadata = []) {
    var key = [];
    key.name = name;
    key.exclusive = exclusive;
    key.checked = checked;
    key.image = image;
    key.image_size = image_size;
    key.metadata = metadata;
    key.function = function () {
        key.checked = this.checked;
    };
    return key;
};

Container.prototype._get_key_tree_with_layer = function (name, layer, checked, iter) {
    var key = [];
    key.name = name;
    key.checked = checked;
    key.arguments = 'api/getobjs?type=' + layer._type + layer.arguments;
    key.function = function () {
        key.checked = this.checked;
        if (this.checked) {
            layer.onLayerAdd(iter);
        } else {
            layer.onLayerRemove(iter);
        }
    };
    return key;
};

Container.prototype.Set_user_event = function (func) {
    this._user_event = func;
};

Container.prototype.Set_near = function (zoom) {
//    var two = 1;
//    if(!(18 - zoom - 2) > 1) {
//        this.near; return;
//    }
//    for(var i = 1; i < (18 - zoom); i++){
//        two *= 2;
//    }
//    this.near = two* 50;
    this.near = 1000;
    if (zoom > 10) {
        this.near = 100;
    }
    if (zoom > 12) {
        this.near = 50;
    }
    if (zoom > 13) {
        this.near = 25;
    }
    if (zoom > 15) {
        this.near = 5;
    }
};

Container.prototype.Set_Search_layer = function (color) {
    this.search = new Search(map, L, color);
};

var Open = function (data) {
    OpenMessageMenu(data);
};

Container.prototype.Set_layer_events = function () {
    if (this.Messages !== undefined) {
        this.Messages.TooltipFunc = Open;
    }
};
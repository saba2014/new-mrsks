class Popup_ps extends Popup_extended {
    constructor() {
        super();
        this.onlytext = false;
    }

    parseCollection(item) {
        let geom = item.geometry;
        if (geom.type !== "GeometryCollection") {
            return false;
        }
        else {
            if(geom.geometries[0].type == "Point") {
                return geom.geometries[0].coordinates[0];
            }
            if(geom.geometries[0].type == "Polygon") {
                return geom.geometries[0].coordinates[0][0];
            }
            return false;
        }
    };

    set popup_text(item) {

        let text = '';
        let result = this.parseCollection(item);
        if (result != false) {
            item.geometry.coordinates = result;
        }
        let json = this.get_json(item, this.Report(item), "ps");
        text += this.get_svg(item);
        text += 'Имя: <b>' + item.properties.d_name + '</b>';
        text += this.get_balance(item);
        if (item.properties.tplnr) {
            text += '<br>ТехМесто: <b><span>' + item.properties.tplnr +
                '</span></b><br><button сlass="copy-text" onclick=Popup_extended.copyTechPlaceToClipboard("' + item.properties.tplnr + '") data-clipboard-text="' +
                (item.properties.tplnr === undefined ? 'неизвестно' : item.properties.tplnr) +
                '">Копировать</button>';
        }
        if (!this.onlytext) {
            if (item.properties.tplnr === undefined) {
                text += '<br>';
            }
            text += "<input type='button' value='Выбрать' id='addBtn' onclick='N.addToReport(" + json + ")'/>";
        }
        text += this.status_get(item);

        if (item.properties.additional !== undefined) {
            text += '<br>Трансформаторы:  ';
            var temp = item.properties.additional.transformer;
            if ((temp !== undefined) && (temp.length > 0)) {
                text += temp[0];
                for (var i = 1; i < temp.length; i++) {
                    text += '/ ' + temp[i];
                }
                text += ' кВА';
            } else {
                text += "нет данных";
            }
            temp = item.properties.additional;
            text += '<br>Резерв факт: ';
            if (temp.res_pow_cons_cotr_appl !== undefined) {
                if (temp.pow_cotr === undefined) {
                    temp.pow_cotr = 0;
                }
                if (temp.pow_appl === undefined) {
                    temp.pow_appl = 0;
                }

                text += Math.round((temp.res_pow_cons_cotr_appl + temp.pow_cotr +
                    temp.pow_appl) * 1000) / 1000 + ' МВт';
            } else {
                text += "нет данных";
            }
            text += '<br>Резерв с Дог. и Заяв.: ';
            if (temp.res_pow_cons_cotr_appl !== undefined) {
                text += temp.res_pow_cons_cotr_appl + ' МВт';
            } else {
                text += "нет данных";
            }

            if (item.properties.Voltage <= 35) {
                text += '<br>ЦП: ';
                if (((temp.root_name !== '') && (temp.root_name !== undefined)) &&
                    ((temp.root_tplnr !== '') && (temp.root_tplnr !== undefined)) &&
                    ((temp.root_switchgear !== '') && (temp.root_switchgear !== undefined))) {
                    text += temp.root_name + ' ' + temp.root_tplnr + '; ' + temp.root_switchgear;
                } else {
                    text += "нет данных";
                }
            }
            text += '<br>Резерв. Яч. РУ: ';
            if ((temp.res_box !== undefined) && temp.res_box.length !== 0) {
                var res = "";
                for (var key in temp.res_box) {
                    if (res !== "") {
                        res = "; " + res;
                    }
                    res = key + ' - ' + temp.res_box[key] + res;
                }
                text += res + ' шт.';
            } else {
                text += "отсутствуют";
            }
        }
        if (item.properties.adrr !== undefined) {
            text += '<br>Адресс:' + item.properties.adrr;
        }
        if (item.properties.name0 !== undefined) {
            text += '<br>Допускающий:' + item.properties.name0;
        }
        if (item.properties.name1 !== undefined) {
            text += '<br>Сопровождающий от АО ДСК:' + item.properties.name1;
        }
        if (item.properties.name2 !== undefined) {
            text += '<br>Представитель ПАО «ДЭСК»:' + item.properties.name2;
        }
        if (item.properties.coordinates) {
            text += '<br>Широта:' + item.geometry.coordinates[0] +
                '<br>Долгота:' + item.geometry.coordinates[1] + '';
        }
        if (item.properties.buildingPercent) {
            text += "<br>Процесс строительства: ";
            text += item.properties.buildingPercent + '%';
        }
        if (item.properties.tplnr) {
            text += '<br><a href=\"api/getSap?tplnr=' + item.properties.tplnr + '\">перейти в SAP</a>';
        }
        text += this.get_hrefs(item);
        this._popup_text = text;
    }

    get popup_text() {
        return this._popup_text;
    }

    Report(item) {
        let report = 'Имя: <b>' + item.properties.d_name + '</b><br>Баланс: ' +
            (item.properties.balance === undefined ? "неизвестно" : item.properties.balance) +
            '<br>ТехМесто: <b>' + (item.properties.tplnr === undefined ? "неизвестно" : item.properties.tplnr) + '</b>';
        if (item.properties.id) {
            report += '<br>Идентификатор: <b>' + item.properties.id + '</b>';
        }
        return report;
    }
}
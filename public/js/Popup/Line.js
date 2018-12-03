class Popup_line extends Popup_extended {
    constructor() {
        super();
        this.onlytext = false;
    }

    set popup_text(item) {
        let text = '';
        let json = this.get_json(item, this.Report(item), "ln");
        text += this.get_svg(item);
        text += 'Имя: <b>' + item.properties.d_name + '</b>';
        text += this.get_balance(item);

        text += '<br>ТехМесто: <b><span>' +
            (item.properties.tplnr === undefined ? "неизвестно" : item.properties.tplnr) + '</b>';
        text += this.status_get(item);
        text += '<br><button сlass="copy-text" onclick=Popup_extended.copyTechPlaceToClipboard("' + item.properties.tplnr +
        '") data-clipboard-text="' +
        (item.properties.tplnr === undefined ? 'неизвестно' : item.properties.tplnr) +
        '">Копировать</button></span>';
        if (!this.onlytext) {
            text += "<input type='button' value='Выбрать' id='addBtn' onclick='N.addToReport(" + json + ")'/>";
        }
        if (item.properties.addition !== undefined) {
            if (item.properties.addition.wires.length) {
                text += '<br>Провод: ';
                for (let i = 0; i < item.properties.addition.wires.length; i++) {
                    text += 'от ' + item.properties.addition.wires[i].p_begin + ' до ' +
                        item.properties.addition.wires[i].p_end + ' ' +
                        item.properties.addition.wires[i].length + ' км. ' +
                        item.properties.addition.wires[i].marka;
                    if (i < item.properties.addition.wires.length - 1) {
                        text += ";<br>";
                    }
                }
                text += ".";
            }
            text += '<br>Загрузка факт: ' + (item.properties.addition.max_amperage === undefined ? "нет данных" :
                item.properties.addition.max_amperage + " А");
            text += '<br>Заявки: ' + (item.properties.addition.specifications === undefined ? "нет данных" :
                item.properties.addition.specifications + " кВТ");
            text += '<br>Договоры: ' + (item.properties.addition.contracts === undefined ? "нет данных" :
                item.properties.addition.contracts + " кВТ");
            text += '<br>ЦП: ' + (item.properties.addition.root === undefined ? "нет данных" :
                item.properties.addition.root + " " +
                item.properties.addition.root_tplnr + '; ' + item.properties.addition.root_switchgear);
            if (item.properties.buildingPercent) {
                text += "<br>Процесс строительства: ";
                text += item.properties.buildingPercent + '%';
            }
        }
        text += this.get_hrefs(item);
        text += '<br><a href=\"api/getSap?tplnr=' + item.properties.tplnr + '\">перейти в SAP</a>';
        text += this.get_hrefs(item);
        this._popup_text = text;
    }

    get popup_text() {
        return this._popup_text;
    }

    Report(item) {
        let report = 'Имя: <b>' + item.properties.d_name + '</b>';
        report += this.get_balance(item);
        report += '<br>ТехМесто: <b>' +
            (item.properties.tplnr === undefined ? 'неизвестно' : item.properties.tplnr) + '</b>';
        return report;
    }

    Seacrh(e, url) {
        let text = "Координаты на карте: <br /><span id=\"copy-text\" data-clipboard-text=\"" +
            e.lat.toString() + ", " + e.lng.toString() + "\">" + e.lat.toString() +
            ", " + e.lng.toString() + '</span><br><span class="elevat"></span>';

        $.ajax({
            url: url + "/api/features/1?text=" + e.lat.toString() + "%20" + e.lng.toString(),
            dataType: 'jsonp'
        }).done(function (data) {
            if ((data.features.length > 0) && (data.status === 200)) {
                $("<hr>" + data.features[0].attrs.address + "<br>Кадастровый номер: <b>" + data.features[0].attrs.cn + "</b>").appendTo(".leaflet-popup-content");
            }
        });
        return text;
    }
}
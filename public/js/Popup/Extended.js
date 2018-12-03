class Popup_extended extends Popup {
    constructor() {
        super();
    }

    set popup_text(item) {
        let name = "Отусутствует";
        if (item.properties.name !== undefined) {
            name = item.properties.name;
        }
        let text = '<b>Наименование:</b> ' + name;
        text += this.get_info(item);
        if (item.properties.buildingPercent !== undefined) {
            text += "<br><b>Процент строительства</b>: " + item.properties.buildingPercent;
        }
        text += this.get_hrefs(item);
        this._popup_text = text;
    }

    get popup_text() {
        return this._popup_text;
    }

    get_info(item) {
        var text = "";
        if ((item.properties.info !== undefined)&&(item.properties.info !== null)) {
            var lines = item.properties.info.split(/\r\n|\r|\n/g);
            for (var i = 0; i < lines.length; i++) {
                text += '<br>' + lines[i];
            }
        }
        return text;
    }

    get_json(item, text, type) {
        var sss;
        var sObj = {
            oLocation: {
                latitude: item.geometry.coordinates[1],
                longitude: item.geometry.coordinates[0]
            },
            oType: "city",
            oTxt: text,
            oOType: type
        };
        sss = JSON.stringify(sObj);
        return sss;
    }

    get_balance(item) {
        var text = "";
        if (item.properties.balance_name !== undefined) {
            text += '<br>Баланс: ' + item.properties.balance_name;
        } else {
            if (item.properties.balance !== undefined) {
                text += '<br>Баланс: ' + item.properties.balance;
            }
        }
        return text;
    }

    status_get(item) {
        var text = "";
        if (item.properties.sysstat !== undefined) {
            text += "<br>Статус: ";
            if (item.properties.sysstat.length > 0) {
                text += item.properties.sysstat[0];
                for (var i = 1; i < item.properties.sysstat.length; i++) {
                    text += ", " + item.properties.sysstat[i];
                }
            }
            if (item.properties.usrstat.length > 0) {
                if (item.properties.sysstat.length > 0) {
                    text += ", ";
                }
                text += item.properties.usrstat[0];
                for (var i = 1; i < item.properties.usrstat.length; i++) {
                    text += ", " + item.properties.usrstat[i];
                }
            }
        }
        return text;
    }

    get_svg(item) {
        var icon = "";
        var d = 20;
        if (item.geometry.type === "Point") {
            if ((item.properties.type === "PS") || (item.properties.type === "PSLoss")) {
                if (item.properties.oTypePS === "1") { // квадрат
                    icon = icon +
                        '<rect x="15" y="' + (d - 5) + '" width="10" height="10"' +
                        'fill="' + item.properties.kVoltage + '" stroke="' + item.properties.kVoltage + '"/>';
                }
                if (item.properties.oTypePS === "2") { // треугольник
                    icon = icon +
                        '<path stroke="#000000" id="svg_1" d="M ' + (2 + 15) + ' ' + (14 + d - 5) + ' L ' + (8 + 15) + ' ' + (2 + d - 5) + ' L ' + (14 + 15) + ' ' + (14 + d - 5) + ' z" stroke-width="1" fill="' + item.properties.kVoltage + '"/>';
                }
                if (item.properties.oTypePS === "3") { // круг
                    icon = icon +
                        '<circle r="7" cy="' + d + '" cx="22" fill="' + item.properties.kVoltage + '"/>';
                }
            } else {
                icon = icon +
                    '<circle r="5" cy="' + d + '" cx="22" fill="' + item.properties.kVoltage + '"/>';
            }
        } else if (item.geometry.type === "LineString") {
            icon = icon +
                '<rect x="15" y="' + d + '" width="15" height="2"' +
                'fill="' + item.properties.kVoltage + '" stroke="' + item.properties.kVoltage + '"/>';
        }
        var main_content = '<div class="svg_img"><svg xmlns="http://www.w3.org/2000/svg" height="50" width="30">' + icon + '</svg></div>';
        return main_content;
    }

    getSap(item) {
        let path = window.location.host + window.location.pathname + 'api/getSap?tplnr=' + item.properties.tplnr;
        var xhr = new XMLHttpRequest();
        xhr.open('GET', path, true);
        xhr.send();
        xhr.onreadystatechange = () => {
            if (xhr.readyState != 4) return;
            if (xhr.status != 200) {
                alert(xhr.status + ': ' + xhr.statusText);
            } else {
                return xhr.responseText;
            }
        }
    }

    //функция костыль для копирования техмест в буфер по нажатию на кнопку "Копировать" в соответствующем попап окне
    static copyTechPlaceToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            var successful = document.execCommand('copy');
            var msg = successful ? 'successful' : 'unsuccessful';
        } catch (err) {
            console.error('Fallback: Oops, unable to copy', err);
        }

        document.body.removeChild(textArea);
    }
}
class PopupResCenter extends Popup {
    constructor(type, _type) {
        super();
        this.type = type;
        this._type = _type;
    }

    set popup_text(item) {
        let text = "";
        let tmp = "";
        if(this._type === "Rise") {
            tmp = "РИСЭ";
        }
        if(this._type === "EmergencyReserve") {
            tmp = "Аварийный резерв";
        }
        if((this._type === "ResCenter")&&(item.properties.type == "res")) {
            tmp = "Центр РЭС";
        }
        if((this._type === "ResCenter")&&(item.properties.type == "po")) {
            tmp = "Центр ПО";
        }
        if(tmp !== "") {
            text += "<p><b>Тип объекта: </b>" + tmp + "</p>";
        }
        if (item.properties.address !== undefined) {
            text += "<p><b>Адрес: </b>" + item.properties.address + "</p>";
        }
        text += "<p><b>Принадлежность: </b></p>";
        if (item.properties.filiation !== undefined) {
            text += "<p><b>Филиал: </b>" + item.properties.filiation + "</p>";
        }
        if (item.properties.po !== undefined) {
            text += "<p><b>ПО: </b>" + item.properties.po + "</p>";
        }

        if (item.properties.res !== undefined) {
            text += "<p><b>РЭС: </b>" + item.properties.res + "</p>";
        }
        // доп. поля для...
        if(this._type === "Rise") {
            if (item.properties.transport !== undefined) {
                tmp = (item.properties.transport) ? "Прицеп" : "Погрузка";
                 text += "<p><b>Тип РИСЭ: </b>" + tmp + "</p>";
            }
            if(item.properties.voltage !== undefined) {
                text += "<p><b>Мощность: </b>" + item.properties.voltage + "</p>";
            }
        }
        this._popup_text = text;
    }

    get popup_text() {
        return this._popup_text;
    }
}
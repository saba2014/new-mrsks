class Popup_CriticalObjects extends Popup_extended {
    constructor() {
        super();
    }

    set popup_text(item) {
        let text = 'Наименование: <b><span>' + item.properties.d_name + '</b><br>';
        text += 'Принадлежность: <span>' + item.properties.balance_name + '<br>';
        text += 'Место расположения: <span>' + item.properties.address + '<br>';
        if(item.properties.kVoltage !== undefined) {
            text += 'Класс напряжения: <span>' + item.properties.kVoltage + 'кВ<br>';
        }
        this._popup_text = text;
    }

    get popup_text() {
        return this._popup_text;
    }

    bind_popup(layer, cheack_near) {
        layer.bindPopup(this.popup_text, {
            keepInView: true,
            cheack_near: cheack_near
        });
    }
}
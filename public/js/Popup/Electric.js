class Popup_electric extends Popup_extended {
    constructor() {
        super();
        this.onlytext = false;
    }

    set popup_text(item) {
        let text = '';
        //text += this.get_svg(item);
        text += 'Имя: <b>' + item.properties.d_name + '</b>';
        text += this.get_balance(item);

        text += '<br>ТехМесто: <b><span>' +
                (item.properties.tplnr === undefined ? "неизвестно" : item.properties.tplnr) + '</b>';
        text += this.get_info(item);
        text += this.get_hrefs(item);
        this._popup_text = text;
    }

    get popup_text() {
        return this._popup_text;
    }
}

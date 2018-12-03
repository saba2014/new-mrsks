class Popup_loss extends Popup_extended {
    constructor() {
        super();
        this.onlytext = false;
    }

    set popup_text(item) {
        let coltyp1 = this.createColorByValue(item.properties.loss.color);
        let coltyp2 = "#000000";

        if ((coltyp1 === "#000000") || (coltyp1 === "#0000FF"))
            coltyp2 = "#FFFFFF";
        let period = this.period_get(item);
        let text = "<table style=\"border-collapse: collapse;\">" +
                "<tr height=\"5px\"><td colspan=\"4\"></td></tr>" +
                "<tr><td class=\"b1\" width=\"1\">Имя</td><td class=\"b1\" colspan=\"3\"><b>" +
                item.properties.d_name + "</b></td></tr>" +
                "<tr><td class=\"b1\">Потери</td><td class=\"b1\" colspan=\"3\" style=\"background-color: " + coltyp1 +
                "; color: " + coltyp2 + "\"><b>" + item.properties.loss.loss_all_pr + "%</b></td></tr>" +
                "<tr><td class=\"b1\">Баланс</td><td class=\"b1\" colspan=\"3\">" + item.properties.balance + "</td></tr>" +
                "<tr><td class=\"b1\">Техместо</td><td class=\"b1\" colspan=\"3\"><b>" + item.properties.tplnr +
                "</b></td></tr>" +
                "<tr height=\"5px\"><td colspan=\"4\"></td></tr>" +
                "<tr><td>Потери&nbsp;за&nbsp;период:</td><td colspan=\"3\"><b>" + period + "</b></td></tr>" +
                "<tr><td class=\"b1\">&nbsp;</td><td class=\"b1\">Юр.</td><td class=\"b1\">Физ.</td><td class=\"b1\">" +
                "<b>Итого</b></td></tr>" +
                "<tr><td class=\"b1\">По кВт/ч</td>" +
                "<td class=\"b1\">" + item.properties.loss.po_jur + "</td>" +
                "<td class=\"b1\">" + item.properties.loss.po_phys + "</td>" +
                "<td class=\"b1\"><b>" + item.properties.loss.po_all + "</b></td></tr>" +
                "<tr><td class=\"b1\">Абоненты</td>" +
                "<td class=\"b1\">" + item.properties.loss.count_jur + "</td>" +
                "<td class=\"b1\">" + item.properties.loss.count_fis + "</td>" +
                "<td class=\"b1\"><b>" + item.properties.loss.count_all + "</b></td></tr>" +
                "<tr><td class=\"b1\">Абоненты&nbsp;АИИСКУЭ</td>" +
                "<td class=\"b1\">" + item.properties.loss.count_askue_jur + "</td>" +
                "<td class=\"b1\">" + item.properties.loss.count_askue_fis + "</td>" +
                "<td class=\"b1\"><b>" + item.properties.loss.count_askue_all + "</b></td></tr>" +
                "<tr><td class=\"b1\" colspan=\"3\">Абоненты&nbsp;без&nbsp;АИИСКУЭ</td>" +
                "<td class=\"b1\"><b>" + item.properties.loss.count_non_askue + "</b></td></tr>" +
                "<tr height=\"5px\"><td colspan=\"4\"></td></tr>" +
                "<tr><td class=\"b1\">Отпуск в&nbsp;сеть&nbsp;кВт/ч</td><td class=\"b1\">Полезный&nbsp;отпуск</td>" +
                "<td class=\"b1\" colspan=\"2\">Потери</td></tr>" +
                "<tr><td class=\"b1\"><b>" + item.properties.loss.fider_input + "</b></td>" +
                "<td class=\"b1\"><b>" + item.properties.loss.po_all + "</b></td>" +
                "<td class=\"b1\" colspan=\"2\" style=\"background-color: " + coltyp1 + "; color: " + coltyp2 + ";\"><b>" +
                item.properties.loss.loss_all + "</b></td></tr></table><hr>" +
                "<button сlass=\"copy-text\" data-clipboard-text=\"" + item.properties.tplnr +
                "\">Копировать Техместо</button></span></b>";
        this._popup_text = text;
    }
    
    get popup_text() {
        return this._popup_text;
    }
    
    period_get(item) {
        var period = "", s1, d1, s11, s2, d2, s21;
        if (item.properties.loss.noloss === 1) {
            period = "<font color=\"red\">Не загружено</font>";
        } else {
            if (item.properties.loss.date_ab === undefined) {
                s1 = "<i>не указано</i>";
            } else {
                d1 = new Date(item.properties.loss.date_ab);
                if (d1.toString() === "Invalid Date") {
                    s1 = "<i>не указано</i>";
                } else {
                    s11 = "";
                    if (d1.getMonth() + 1 <= 9)
                        s11 = "0";
                    s1 = d1.getDate() + "." + s11 + (d1.getMonth() + 1) + "." + d1.getFullYear();
                }
            }
            if (item.properties.loss.date === undefined) {
                s2 = "<i>не указано</i>";
            } else {
                d2 = new Date(item.properties.loss.date);
                if (d2.toString() === "Invalid Date") {
                    s2 = "<i>не указано</i>";
                } else {
                    s21 = "";
                    if (d2.getMonth() + 1 <= 9)
                        s21 = "0";
                    s2 = d2.getDate() + "." + s21 + (d2.getMonth() + 1) + "." + d2.getFullYear();
                }
            }
            period = "с " + s1 + " по " + s2;
        }
        return period;
    }
    
    createColorByValue(value) {
        var colr;
        switch (value) {
            case "RED":
                colr = "#ff0000"; // красный
                break;
            case "GREN":
                colr = "#00ff00"; // Зеленый
                break;
            case "YELW":
                colr = "#fff400"; // Желтый
                break;
            case "BLAK":
                colr = "#000000"; // черный
                break;
            case "BLUE":
                colr = "#0000FF"; // синий
                break;
            default:
                colr = "#000000"; // черный
        }
        return colr;
    }
    
    bind_popup(layer, cheack_near) {
        layer.bindPopup(this.popup_text, {
            keepInView: true,
            maxWidth: 560,
            maxHeight: 560,
            cheack_near: cheack_near
        });
    }
}
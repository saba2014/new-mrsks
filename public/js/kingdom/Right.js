class Right {
    constructor(info) {
        this.name = info.attribute;
        this.amount = 0;
        if (info.count){
            if (info.count===-1)
                this.amount='&#8734;';
            else this.amount=info.count;
        }
       // if (isNaN(this.amount)) console.log(info);
        this.inUse = 0;
        this.active = true;
        this.color = info.color;
        this.style = "color: " + this.color;
        this.parent = info.parent;
        this.dis = false;
    }

    decreaseAmount() {
        if (this.amount !== 0 && this.active === true) {
            this.amount--;
            this.inUse++;
            if (this.amount === 0) this.dis = true;
            return true;
        }
        return false;
    }

    returnOne(){
        if (this.active == true)
        {
            this.inUse--;
            this.amount++;
        }
    }

    returnAmount(){
        this.amount+=this.inUse;
        this.inUse = 0;
    }

}
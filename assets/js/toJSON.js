(function($){
    $.fn.toJSON = function(options){
        options = $.extend({}, options);
        var self = this,
            json = {},
            push_counters = {},
            patterns = {
                "validate": /^[a-zA-Z_\-][a-zA-Z0-9_\-]*(?:\[(?:\d*|[a-zA-Z0-9_\-]+)\])*$/,
                "key":      /[a-zA-Z0-9_\-]+|(?=\[\])/g,
                "push":     /^$/,
                "fixed":    /^\d+$/,
                "named":    /^[a-zA-Z0-9_\-]+$/
            };
        this.build = function(base, key, value){
            base[key] = value;
            return base;
        };
        this.push_counter = function(key){
            if(push_counters[key] === undefined){
                push_counters[key] = 0;
            }
            return push_counters[key]++;
        };

        $.each($(this).serializeArray(), function(){
			//alert(this.name)
            // skip invalid keys
            if(!patterns.validate.test(this.name)){
                return;
            }
			//alert(this.name)
            var k,
                keys = this.name.match(patterns.key),
                merge = this.value,
                reverse_key = this.name;
            while((k = keys.pop()) !== undefined){
                // adjust reverse_key
                reverse_key = reverse_key.replace(new RegExp("\\[" + k + "\\]$"), '');
                // push
                if(k.match(patterns.push)){
					var key = self.push_counter(reverse_key);
                    merge = self.build([], key, merge);
                }
                // fixed
                else if(k.match(patterns.fixed)){
                    merge = self.build([], k, merge);
                }

                // named
                else if(k.match(patterns.named)){
                    merge = self.build({}, k, merge);
                }
            }
            json = $.extend(true, json, merge);
        });
		var trimObject = function(obj){
			if(typeof obj == 'undefined') return ;
			if(obj.constructor === Array){				
				obj.clean(null);
				for(var i = 0; i < obj.length;i++) trimObject(obj[i]);
			}else if(obj.constructor === Object){
					
				for(var i in obj){
					trimObject(obj[i]);
				}
			}
			
		}
		//trimObject(json);
        return json;
    };

    jQuery.fn.serializeObject = function() {
        var arrayData, objectData;
        arrayData = this.serializeArray();
        objectData = {};

        $.each(arrayData, function() {
            var value;

            if (this.value != null) {
                value = this.value;
            } else {
                value = '';
            }

            if (objectData[this.name] != null) {
                if (!objectData[this.name].push) {
                    objectData[this.name] = [objectData[this.name]];
                }

                objectData[this.name].push(value);
            } else {
                objectData[this.name] = value;
            }
        });

        return objectData;
    };
})(jQuery);   
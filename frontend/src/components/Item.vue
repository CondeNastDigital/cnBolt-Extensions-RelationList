<template>
    <b-row>
        <div class="col-xs-3 text-center">
            <b-card-img :alt="teaser.title"
                        :src="teaser.image"
                        v-if="teaser.image"
                        class="img-responsive"
            />
        </div>
        <div class="col-xs-9">
            <div class="service label" :class="serviceClasses()" :title="`Source: ${service}`">
                <img :src="require(`./../assets/images/icons/icon-${service}.png`)">
                {{ service.charAt(0).toUpperCase() + service.slice(1) }}
            </div>
            <h5 class="mt-0">{{ teaser.title }}</h5>
            <p>{{ teaser.description }}</p>
        </div>
    </b-row>
</template>

<script>
    export default {

        props: [
            'id',
            'service',
            'teaser',
        ],

        methods: {
            //todo: pass the extension config to made the configurations for the icons in the extension.yml
            serviceClasses: function () {
                switch (this.service) {
                    case "content":
                        return "label-primary";
                    case "kraken":
                        return "label label-default";
                }
            }
        },

        data() {
            return {}
        }
    }
</script>

<style scoped>
    .service.label {
        display: inline-block;
    }

    .service img {
        width: 16px;
        height: auto;
    }
</style>
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

            <Badge
                    v-for="badge in badges"
                    :key="badge"
                    :badge="badge"
            ></Badge>

            <h5 class="mt-0" v-if="this.teaser.link">
                <a :href="this.teaser.link" target="_blank">{{ this.teaser.title }}</a>
            </h5>
            <h5 class="mt-b" v-else>
                {{ this.teaser.title }}
            </h5>

            <p>{{ truncate(teaser.description, 120) }}</p>
        </div>
    </b-row>
</template>

<script>

    import Badge from "./Badge.vue";

    export default {

        props: [
            'id',
            'service',
            'teaser',
            'type'
        ],

        components: {
            Badge
        },

        methods: {
          truncate: function(string, length, clamp = '...'){
            return string.length > length ? string.slice(0, length) + clamp : string;
          }
        },

        computed: {

            badges: {
                get() {
                    return {
                        service: {
                            label: this.service.charAt(0).toUpperCase() + this.service.slice(1) || false,
                            cssclass: 'label-primary'
                        },
                        /*
                        brand: {
                            label: (this.service !== 'content') ? (this.teaser.title.split('-')[0]).trim() : false
                        },
                        */
                        type: {
                            label: this.type.charAt(0).toUpperCase() + this.type.slice(1) || false
                        }
                    }
                }
            }
        },

        data() {
            return {}
        }
    }
</script>

<style scoped>
    a,
    a:link,
    a:hover,
    a:active,
    a:visited,
    a:focus {
        color: inherit;
        text-decoration: none;
        font-weight: 500;
    }
    a:hover{
        text-decoration: underline;
    }
    img {
        max-width: 150px;
        width: 100%;
    }
    p {
        font-size: 11px;
    }

</style>

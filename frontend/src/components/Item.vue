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

            <h5 class="mt-0">{{ teaser.title }}</h5>
            <p>{{ teaser.description }}</p>
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
    img {
        max-width: 150px;
        width: 100%;
    }
</style>

<template>
    <b-row>
        <div class="col-xs-3 text-center">
            <b-card-img v-if="thumbnail"
                        :alt="title"
                        :src="thumbnail"
                        class="img-responsive"
            />
        </div>
        <div class="col-xs-9">
            <h5 class="mt-0">{{ title }}</h5>
            <p>{{ excerpt }}</p>
        </div>
    </b-row>
</template>

<script>

    export default {

        props: [
            'id',
            'title',
            'thumbnail',
            'excerpt',
        ],

        data() {
            return {}
        }
    }
</script>
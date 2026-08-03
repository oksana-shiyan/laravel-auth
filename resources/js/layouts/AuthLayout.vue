<template>
    <v-app>
        <v-main>
            <v-container class="fill-height">
                <v-row align="center" justify="center">
                    <v-col cols="12" sm="10" md="7" lg="5">
                        <router-view />
                    </v-col>
                </v-row>
            </v-container>
        </v-main>
    </v-app>
</template>

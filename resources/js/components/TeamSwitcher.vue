<script>
    module.exports = {
        data: function () {
            return {
                isLoading: true,
                teams: [],
                currentTeamId: null,
                isSwitching: false,
            };
        },

        created: function () {
            this.loadTeams();
        },

        methods: {
            loadTeams: function () {
                var self = this;

                axios.get("/genealabs/laravel-governor/nova/team-switcher")
                    .then(function (response) {
                        self.teams = response.data.teams;
                        self.currentTeamId = response.data.currentTeamId;
                        self.isLoading = false;
                    });
            },

            switchTeam: function (teamId) {
                if (this.isSwitching || teamId === this.currentTeamId) {
                    return;
                }

                var self = this;
                self.isSwitching = true;

                axios.post("/genealabs/laravel-governor/nova/team-switcher", {
                    team_id: teamId,
                })
                    .then(function (response) {
                        self.currentTeamId = response.data.currentTeamId;
                        self.$toasted.show("Team switched successfully!", {
                            type: "success",
                        });
                    })
                    .catch(function (error) {
                        self.$toasted.show("Failed to switch team.", {
                            type: "error",
                        });
                    })
                    .finally(function () {
                        self.isSwitching = false;
                    });
            },
        },
    };
</script>

<template>
    <loading-card :loading="isLoading">
        <div class="px-3 py-4">
            <h3 class="flex mb-3 text-base text-80 font-bold">
                Switch Team
            </h3>

            <p v-if="teams.length === 0" class="text-sm text-80">
                You are not a member of any teams.
            </p>

            <ul v-else class="list-reset">
                <li
                    v-for="team in teams"
                    :key="team.id"
                    class="flex items-center py-2 cursor-pointer hover:bg-30 rounded px-2"
                    :class="{ 'bg-20': team.id === currentTeamId }"
                    @click="switchTeam(team.id)"
                >
                    <span
                        class="inline-block rounded-full w-2 h-2 mr-3"
                        :class="team.id === currentTeamId ? 'bg-success' : 'bg-60'"
                    ></span>
                    <span class="text-sm" :class="team.id === currentTeamId ? 'text-success font-bold' : 'text-80'">
                        {{ team.name }}
                    </span>
                </li>
            </ul>
        </div>
    </loading-card>
</template>

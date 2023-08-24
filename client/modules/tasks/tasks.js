({
    queues: [],

    init() {
        moduleLoaded("tasks", this)

        if (AVAIL("tasks", "queues", "GET")) {
            GET("tasks", "queues")
                .done((response) => {
                    modules.tasks.queues = response

                    $(`
                        <li class="nav-item dropdown">
                            <a id="tasksMenuRight" class="nav-link text-dark" data-toggle="dropdown" title="${i18n("tasks.title")}" href="#">
                                <i class="fas fa-lg fa-fw fa-server"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-3" style="max-width: none;">
                                <p>Статус текущих задач</p>
                                <div class="dropdown-divider"></div>
                                <div id="tasksMenuRightContainer">
                                    ${modules.tasks.queues.map((queue) => `<p>Очередь (${queue}): 0</p>`)}
                                </div>
                            </div>
                        </li>
                    `).insertAfter("#rightTopDynamic");

                    $("#tasksMenuRight").off("click", modules.tasks.tasksMenuRight).on("click", modules.tasks.tasksMenuRight);
                })
        }
    },

    tasksMenuRight() {
        console.log(modules.tasks.queues)
    }
}).init()
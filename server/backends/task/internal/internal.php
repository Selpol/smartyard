<?php

namespace backends\task;

class internal extends task
{
    public function page(int $size, int $page): array
    {
        return $this->db->get('SELECT * FROM task OFFSET :page LIMIT :size', ['page' => $page * $size, 'size' => $size]);
    }

    public function add(\Selpol\Task\Task $task, string $message, int $status): int
    {
        $id = $this->db->get("SELECT NEXTVAL('task_id_seq')", options: ['singlify'])['nextval'];

        $task->taskId = $id;

        logger('task-high')->debug($id);

        $statement = $this->db->prepare('INSERT INTO task(id, data, title, message, status) VALUES (:id, :data, :title, :message, :status)');

        return $statement->execute(['id' => $id, 'data' => serialize($task), 'title' => $task->title, 'message' => $message, 'status' => $status]) ? $id : -1;
    }

    public function update(int $id, string $message, int $status): bool
    {
        return $this->db->modify('UPDATE task SET message = :message, status = :status, updated_at = NOW() WHERE id = :id', ['id' => $id, 'message' => $message, 'status' => $status]);
    }

    public function dispatch(int $id): bool
    {
        $dbTask = $this->db->get('SELECT data FROM task WHERE id = :id', ['id' => $id], options: ['singlify']);

        if (!$dbTask)
            return false;

        $task = unserialize($dbTask['data']);

        if ($task instanceof \Selpol\Task\Task)
            return task($task)->high()->dispatch();

        return false;
    }
}
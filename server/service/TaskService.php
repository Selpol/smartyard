<?php declare(strict_types=1);

namespace Selpol\Service;

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Selpol\Feature\Task\TaskFeature;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Framework\Container\ContainerDisposeInterface;
use Selpol\Framework\Kernel\Exception\KernelException;
use Selpol\Task\Task;
use Selpol\Task\TaskCallbackInterface;

#[Singleton]
class TaskService implements LoggerAwareInterface, ContainerDisposeInterface
{
    use LoggerAwareTrait;

    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public const QUEUE_HIGH = 'high';
    public const QUEUE_LOW = 'low';
    public const QUEUE_DEFAULT = 'default';

    private static ?TaskService $instance = null;

    public function __construct()
    {
        $this->setLogger(file_logger('task'));
    }

    /**
     * @throws Exception
     */
    public function connect(): void
    {
        $this->connection = new AMQPStreamConnection(config_get('amqp.host'), config_get('amqp.port'), config_get('amqp.username'), config_get('amqp.password'));
        $this->channel = $this->connection->channel();
    }

    /**
     * @throws Exception
     */
    public function enqueue(string $queue, Task $task, ?int $delay): void
    {
        $feature = container(TaskFeature::class);

        if ($feature->hasUnique($task))
            throw new KernelException('Задача уже существует');

        $feature->setUnique($task);

        if ($this->connection == null || $this->channel == null)
            $this->connect();

        $this->channel->exchange_declare('delayed_exchange', 'x-delayed-message', durable: true, arguments: new AMQPTable(['x-delayed-type' => 'direct']));

        $this->channel->queue_declare($queue, durable: true);
        $this->channel->queue_bind($queue, 'delayed_exchange', $queue);

        $message = new AMQPMessage(serialize($task), ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        if ($delay)
            $message->set('application_headers', new AMQPTable(['x-delay' => $delay * 1000]));

        $this->channel->basic_publish($message, 'delayed_exchange', $queue);

        $this->logger?->info('Enqueue task', ['queue' => $queue, 'title' => $task->title, 'delay' => $delay]);
    }

    /**
     * @throws Exception
     */
    public function dequeue(string $queue, callable|TaskCallbackInterface $callback): void
    {
        if ($this->connection == null || $this->channel == null)
            $this->connect();

        $this->channel->queue_declare($queue, durable: true);

        $logger = $this->logger;

        $this->channel->basic_consume($queue, no_ack: true, callback: static function (AMQPMessage $message) use ($callback, $logger) {
            try {
                $task = unserialize($message->body);

                if ($task instanceof Task)
                    $callback($task);
            } catch (Exception $exception) {
                $logger?->error($exception);
            }
        });

        while ($this->channel->is_consuming())
            $this->channel->wait();
    }

    /**
     * @throws Exception
     */
    public function close(): void
    {
        $this->channel?->close();
        $this->connection?->close();

        $this->channel = null;
        $this->connection = null;
    }

    public function dispose(): void
    {
        try {
            $this->close();
        } catch (Exception $exception) {
            $this->logger?->critical($exception);
        }
    }
}
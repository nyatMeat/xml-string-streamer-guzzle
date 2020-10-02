<?php

namespace Prewk\XmlStringStreamer\Stream;

use Exception;
use GuzzleHttp\Psr7\CachingStream;
use function GuzzleHttp\Psr7\stream_for;
use Prewk\XmlStringStreamer\StreamInterface;

class Guzzle implements StreamInterface
{
	/** @var \Psr\Http\Message\StreamInterface */
	private $stream;

	/** @var int */
	private $readBytes = 0;

	/** @var int */
	private $chunkSize;

	/** @var callable|null */
	private $chunkCallback;


	/**
	 * Create instance for psr7 stream
	 * @param $fileUrl
	 * @param int $chunkSize
	 * @param null $chunkCallback
	 * @return Guzzle
	 */
	public static function createForFile(string $fileUrl, $chunkSize = 1024, $chunkCallback = null)
	{
		return (new static())
			->setStream(new CachingStream(
				stream_for(fopen($fileUrl, 'r')
				)))
			->setChunkSize($chunkSize)
			->setChunkCallback($chunkCallback);
	}

	/**
	 * Create instance for psr7 stream
	 * @param \Psr\Http\Message\StreamInterface $stream
	 * @param int $chunkSize
	 * @param null $chunkCallback
	 * @return Guzzle
	 */
	public static function createForPsrStream(\Psr\Http\Message\StreamInterface $stream, $chunkSize = 1024, $chunkCallback = null)
	{
		return (new static())
			->setStream($stream)
			->setChunkSize($chunkSize)
			->setChunkCallback($chunkCallback);
	}

	public function getChunk()
	{
		if (!$this->stream->eof())
		{
			$buffer = $this->stream->read($this->chunkSize);
			$this->readBytes += strlen($buffer);

			if (is_callable($this->chunkCallback))
			{
				call_user_func_array($this->chunkCallback, [$buffer, $this->readBytes]);
			}

			return $buffer;
		}
		else
		{
			return false;
		}
	}

	public function isSeekable()
	{
		return $this->stream->isSeekable();
	}

	public function rewind()
	{
		if ($this->isSeekable() === false)
		{
			throw new Exception('Attempted to rewind an unseekable stream.');
		}

		$this->readBytes = 0;
		$this->stream->rewind();
	}

	/**
	 * @param \Psr\Http\Message\StreamInterface $stream
	 * @return Guzzle
	 */
	protected function setStream(\Psr\Http\Message\StreamInterface $stream): Guzzle
	{
		$this->stream = $stream;
		return $this;
	}

	/**
	 * @param int $chunkSize
	 * @return Guzzle
	 */
	protected function setChunkSize(int $chunkSize): Guzzle
	{
		$this->chunkSize = $chunkSize;
		return $this;
	}

	/**
	 * @param callable|null $chunkCallback
	 * @return Guzzle
	 */
	protected function setChunkCallback(?callable $chunkCallback): Guzzle
	{
		$this->chunkCallback = $chunkCallback;
		return $this;
	}


}

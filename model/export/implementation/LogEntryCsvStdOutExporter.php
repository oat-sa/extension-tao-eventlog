<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2019 (original work) Open Assessment Technologies SA;
 *
 */

namespace oat\taoEventLog\model\export\implementation;

use oat\taoEventLog\model\export\LogEntryRepositoryInterface;

class LogEntryCsvStdOutExporter
{
    /**
     * @var LogEntryRepositoryInterface
     */
    private $generator;
    /**
     * @var string
     */
    private $delimiter;
    /**
     * @var string
     */
    private $enclosure;

    /**
     * @param LogEntryRepositoryInterface $generator
     * @param string $delimiter
     * @param string $enclosure
     */
    public function __construct(LogEntryRepositoryInterface $generator, $delimiter = ',', $enclosure = '"')
    {
        $this->generator = $generator;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
    }

    /**
     * @return string
     */
    public function export()
    {
        $this->sendHeaders('export.csv');
        $this->echoContent();
    }

    protected function sendHeaders($fileName = null)
    {
        if ($fileName === null) {
            $fileName = (string)time();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; fileName="' . $fileName . '"');
    }

    private function echoContent()
    {
        $out = fopen('php://output', 'wb');
        if (false === $out) {
            throw new \RuntimeException('Can not open stdout for writing');
        }

        foreach ($this->generator->fetch() as $row) {
            if (!empty($row)) {
                $result = fputcsv($out, $row, $this->delimiter, $this->enclosure);
                if (false === $result) {
                    throw new \RuntimeException('Can not write to stdout');
                }
            }
        }

        fclose($out);
    }
}

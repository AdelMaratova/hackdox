<?php
/**
 * Copyright (c) 2010-2012 Arne Blankerts <arne@blankerts.de>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 *   * Neither the name of Arne Blankerts nor the names of contributors
 *     may be used to endorse or promote products derived from this software
 *     without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT  * NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER ORCONTRIBUTORS
 * BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    phpDox
 * @author     Arne Blankerts <arne@blankerts.de>
 * @copyright  Arne Blankerts <arne@blankerts.de>, All rights reserved.
 * @license    BSD License
 */
namespace TheSeer\phpDox\Collector {

    use TheSeer\DirectoryScanner\DirectoryScanner;
    use TheSeer\phpDox\ProgressLogger;
    use TheSeer\phpDox\Collector\Backend\BackendInterface;
    use TheSeer\phpDox\Project\Project;

    /**
     * Collector processing class
     */
    class Collector {

        /**
         * @var \TheSeer\phpDox\ProgressLogger
         */
        private $logger;

        /**
         * @var \TheSeer\phpDox\Project\Project
         */
        private $project;

        /**
         * @var array
         */
        private $parseErrors = array();

        /**
         * @var
         */
        private $backend;

        /**
         * @param \TheSeer\phpDox\ProgressLogger  $logger
         * @param Project $project
         */
        public function __construct(ProgressLogger $logger, Project $project) {
            $this->logger = $logger;
            $this->project = $project;
        }

        /**
         * @param \TheSeer\DirectoryScanner\DirectoryScanner $scanner
         * @param                                            $backend
         *
         *
         */
        public function run(DirectoryScanner $scanner, BackendInterface $backend) {
            $this->backend = $backend;

            $srcDir = $this->project->getSourceDir();
            $this->logger->log("Scanning directory '{$srcDir}' for files to process\n");
            foreach($scanner($srcDir) as $file) {
                $nedsProcessing = $this->project->addFile($file);
                if (!$nedsProcessing) {
                    $this->logger->progress('cached');
                    continue;
                }
                $this->processFile($file);
            }
            $this->logger->completed();
            return $this->project;
        }

        /**
         * @return bool
         */
        public function hasParseErrors() {
            return count($this->parseErrors) > 0;
        }

        /**
         * @return array
         */
        public function getParseErrors() {
            return $this->parseErrors;
        }

        /**
         * @param \SplFileInfo $file
         */
        private function processFile(\SplFileInfo $file) {
            try {
                $result = $this->backend->parse($file);
                if ($result->hasClasses()) {
                    foreach($result->getClasses() as $class) {
                        $this->project->addClass($class);
                    }
                }
                if ($result->hasInterfaces()) {
                    foreach($result->getInterfaces() as $interface) {
                        $this->project->addInterface($interface);
                    }
                }
                if ($result->hasTraits()) {
                    foreach($result->getTraits() as $trait) {
                        $this->project->addTrait($trait);
                    }
                }
                $this->logger->progress('processed');
            } catch (ParseError $e) {
                $this->parseErrors[$file->getPathname()] = $e;
                $this->logger->progress('failed');
            } catch (\Exception $e) {
                throw new CollectorException('Error while processing source file', CollectorException::ProcessingError, $e, $file);
            }
        }

    }
}

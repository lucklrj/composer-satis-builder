<?php

namespace AOE\Composer\Satis\Generator\Builder;

class SatisBuilder
{
    /**
     * @var \stdClass
     */
    private $satis;

    /**
     * @var \stdClass
     */
    private $composer;

    /**
     * @param \stdClass $composer
     * @param \stdClass $satis
     */
    public function __construct(\stdClass $composer, \stdClass $satis)
    {
        $this->composer = $composer;
        $this->satis = $satis;
    }

    /**
     * @return SatisBuilder
     */
    public function resetSatisRequires()
    {
        if (isset($this->satis->require)) {
            $this->satis->require = new \stdClass();
        }
        return $this;
    }

    /**
     * @return SatisBuilder
     */
    public function addRequiresFromComposer()
    {
        if (false === isset($this->satis->require)) {
            $this->satis->require = new \stdClass();
        }
        foreach ($this->composer->require as $package => $version) {
            $this->satis->require->$package = $version;
        }
        return $this;
    }

    /**
     * @return SatisBuilder
     */
    public function mergeRequiresFromComposer()
    {
        if (false === isset($this->satis->require)) {
            $this->satis->require = new \stdClass();
        }
        foreach ($this->composer->require as $package => $version) {
            if (property_exists($this->satis->require, $package)) {
                if (strtolower($package) == "php") {
                    continue;
                }
                if ($this->satis->require->$package == $version) {
                    continue;
                }
                if ($this->satis->require->$package == "*") {
                    continue;
                }
                //todo 先简单合并一下， 等以后有空了再处理各种运算符的合并：^,~,>=,>,<,<=,*等等
                $this_require = explode("|", $this->satis->require->$package);
                foreach ($this_require as $index => $single_version) {
                    if (trim($single_version) == $version) {
                        continue;
                    } else {
                        $this_require[] = $version;
                    }
                }
                $this->satis->require->$package = join(" | ", $this_require);

            } else {
                $this->satis->require->$package = $version;
            }
        }
        return $this;
    }

    /**
     * @return SatisBuilder
     */
    public
    function addDevRequiresFromComposer()
    {
        if (false === isset($this->satis->require)) {
            $this->satis->require = new \stdClass();
        }
        foreach ($this->composer->{'require-dev'} as $package => $version) {
            $this->satis->require->$package = $version;
        }
        return $this;
    }

    /**
     * @param boolean $require
     * @return SatisBuilder
     */
    public
    function setRequireDependencies(
        $require = true
    ) {
        $this->satis->{'require-dependencies'} = (boolean)$require;
        return $this;
    }

    /**
     * @param boolean $require
     * @return SatisBuilder
     */
    public
    function setRequireDevDependencies(
        $require = true
    ) {
        $this->satis->{'require-dev-dependencies'} = (boolean)$require;
        return $this;
    }

    /**
     * @return \stdClass
     */
    public
    function build()
    {
        return $this->satis;
    }
}
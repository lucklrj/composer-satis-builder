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
     * @param string $point
     * @return $this
     */
    public function mergeRequiresFromComposer($point = "require")
    {
        if (false === isset($this->satis->require)) {
            $this->satis->require = new \stdClass();
        }
        foreach ($this->composer->$point as $package => $composer_version) {

            if (property_exists($this->satis->require, $package)) {
                if (strtolower($package) == "php") {
                    continue;
                }
                if ($this->satis->require->$package == $composer_version) {
                    continue;
                }
                if ($this->satis->require->$package == "*") {
                    continue;
                }
                $composer_version = str_replace("||", "|", $composer_version);
                $array_composer_version = explode("|", $composer_version);
                //todo 先简单合并一下， 等以后有空了再处理各种运算符的合并：^,~,>=,>,<,<=,*等等
                $this_require = explode("|", str_replace(" ", "", $this->satis->require->$package));

                foreach ($this_require as $index => $single_version) {
                    $single_version = trim($single_version);
                    foreach ($array_composer_version as $loop_index => $_version) {
                        $_version = trim($_version);
                        if ($_version == "*") {
                            $this_require = ["*"];
                            break 2;
                        } else {
                            if (in_array($_version, $this_require) == true) {
                                continue;
                            } else {
                                $this_require[] = $_version;
                            }
                        }

                    }
                }
                $this->satis->require->$package = join(" | ", $this_require);

            } else {
                $this->satis->require->$package = $composer_version;
            }
        }
        return $this;
    }

    public function mergeRepositoriesFromComposer($type, $url)
    {
        $is_exists = false;
        foreach ($this->satis->repositories as $index => $single_repertory) {
            if (
                property_exists($single_repertory, "type") &&
                $single_repertory->type == $type &&
                property_exists($single_repertory, "url") &&
                $single_repertory->url == $url
            ) {
                $is_exists = true;
                break;
            }
        }
        if ($is_exists === false) {
            $new_repertory = new \stdClass();
            $new_repertory->type = $type;
            $new_repertory->url = $url;
            $new_repertory->{"installation-source"} = "dist";
            $this->satis->repositories[] = $new_repertory;
        }
    }

    /**
     * @return SatisBuilder
     */
    public function addDevRequiresFromComposer()
    {
        return $this->mergeRequiresFromComposer("require-dev");
    }

    /**
     * @param boolean $require
     * @return SatisBuilder
     */
    public function setRequireDependencies(
        $require = true
    ) {
        $this->satis->{'require-dependencies'} = (boolean)$require;
        return $this;
    }

    /**
     * @param boolean $require
     * @return SatisBuilder
     */
    public function setRequireDevDependencies(
        $require = true
    ) {
        $this->satis->{'require-dev-dependencies'} = (boolean)$require;
        return $this;
    }

    /**
     * @return \stdClass
     */
    public function build()
    {
        return $this->satis;
    }
}
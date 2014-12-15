# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"
VAGRANT_REQUIRED_VERSION = "1.2.0"

# Require 1.2.x at least
if ! defined? Vagrant.require_version
  if Gem::Version.new(Vagrant::VERSION) < Gem::Version.new(VAGRANT_REQUIRED_VERSION)
    puts "Vagrant >= " + VAGRANT_REQUIRED_VERSION + " required. Your version is " + Vagrant::VERSION
    exit 1
  end
else
  Vagrant.require_version ">= " + VAGRANT_REQUIRED_VERSION
end

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.network "forwarded_port", guest: 80, host: 8080,
    auto_correct: true

  config.vm.provision :shell, :path => ".puppet/manifests/puppet.sh"

  config.vm.provider :virtualbox do |v, override|
    override.vm.box = "puppetlabs/centos-6.5-64-puppet"

    v.customize ["modifyvm", :id, "--memory", "1024"]
  end

  config.vm.provider :parallels do |p, override|
    override.vm.box = "parallels/centos-6.5"

    p.name = "Icinga Web 2 Development"

    # Update Parallels Tools automatically
    p.update_guest_tools = true

    # Set power consumption mode to "Better Performance"
    p.optimize_power_consumption = false

    p.memory = 1024
    p.cpus = 2
  end

  config.vm.provision :puppet do |puppet|
    puppet.hiera_config_path = ".puppet/hiera/hiera.yaml"
    puppet.module_path = [ ".puppet/modules", ".puppet/profiles" ]
    puppet.manifests_path = ".puppet/manifests"
    puppet.manifest_file = "site.pp"
  end
end

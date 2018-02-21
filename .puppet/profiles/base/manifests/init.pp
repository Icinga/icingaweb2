class base {
  package { [ 'vim-enhanced', 'bash-completion', 'htop' ]:
    ensure => latest,
  }
}

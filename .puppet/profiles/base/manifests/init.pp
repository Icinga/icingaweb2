class base {
  package { [ 'vim-enhanced', 'bash-completion' ]:
    ensure => latest,
  }
}

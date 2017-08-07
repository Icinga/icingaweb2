# Troubleshooting <a id="troubleshooting"></a>

## PageSpeed Module Incompatibility <a id="pagespeed-incompatibility"></a>

It seems that Web 2 is not compatible with the PageSpeed module. Please disable the PageSpeed module using one of the
following methods.

**Apache**:
```
ModPagespeedDisallow "*/icingaweb2/*"
```

**Nginx**:
```
pagespeed Disallow "*/icingaweb2/*";
```


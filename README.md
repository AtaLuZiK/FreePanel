# FreePanel

[![License](https://img.shields.io/badge/license-Apache_2-blue.svg?style=flat-square)](https://www.apache.org/licenses/LICENSE-2.0)

The server administration software for your needs. This panel simplifies the effort of managing your hosting platform.


## Installation

### Fast install

To install the FreePanel, run this command on the shell of the server as root user:
```
install-freepanel.sh
```

The script will then check and guide you trough the process.

The **FreePanel** (included **freepaneld** daemon) will be installed in `/usr/local/freepanel` by default.

If you want to update **freepaneld** only, run the following:
```
./configure
make
make install
```


### Detailed installation

For more details about **freepaneld** installation, refer to [INSTALL](INSTALL).


## Wiki

### Building documents

Building documents requires doxygen. Run `make docs` will generate HTML document only by default or other document format run `configure --help' for more details.

## License

May be found in [LICENSE](LICENSE)

